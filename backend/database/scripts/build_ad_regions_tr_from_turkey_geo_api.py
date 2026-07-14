#!/usr/bin/env python3
"""
يبني config/ad_regions_tr.php من بيانات مشروع turkey-geo-api (MIT).

المصدر: https://github.com/onurusluca/turkey-geo-api
الملفات: provinces.json, districts.json, neighborhoods.json (يُحمَّل إلى database/data/turkey-geo-api/ إن وُجدت الشبكة).

هيكل الأكواد (جديد، يختلف عن ad_regions_tr_metro/bulk القديمة):
  - محافظة: TR-01 … TR-81 (رقم اللوحة بصيغة رقمين)
  - ilçe:     TR-D-{district.id}
  - mahalle:  TR-M-{neighborhood.id}

name_tr من JSON؛ name_en = لاتيني مبسّط (للمطابقة/الإنجليزية).
name_ar: محافظات = مسميات عربية معتمدة؛ ilçe = ملف district_overrides + قاعدة «مركز» + تعريب تركي (حروف وجمعات صوتية)؛ mahalle = نفس التعريب مع ملف neighborhood_overrides الاختياري.

تشغيل من جذر المشروع:
  python3 database/scripts/build_ad_regions_tr_from_turkey_geo_api.py

ثم:
  php artisan geo:rebuild-from-catalog

تحذير: الإعلانات/المستخدمون الذين خزنوا location_* بالأكواد القديمة قد يحتاجون ترحيلاً يدوياً.
"""
from __future__ import annotations

import json
import os
import sys
import urllib.request
from collections import defaultdict

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
DATA_DIR = os.path.join(ROOT, "database", "data", "turkey-geo-api")
GEO_AR_DIR = os.path.join(ROOT, "database", "data", "turkey_geo_ar")
DISTRICT_OVERRIDES_JSON = os.path.join(GEO_AR_DIR, "district_overrides.json")
NEIGHBORHOOD_OVERRIDES_JSON = os.path.join(GEO_AR_DIR, "neighborhood_overrides.json")
OUT = os.path.join(ROOT, "config", "ad_regions_tr.php")

RAW_BASE = "https://raw.githubusercontent.com/onurusluca/turkey-geo-api/main/data/"

_TR_ASCII = str.maketrans(
    {
        "\u0131": "i",
        "\u0130": "I",
        "ğ": "g",
        "Ğ": "G",
        "ü": "u",
        "Ü": "U",
        "ş": "s",
        "Ş": "S",
        "ö": "o",
        "Ö": "O",
        "ç": "c",
        "Ç": "C",
    }
)


def turkish_ascii(s: str) -> str:
    return (s or "").translate(_TR_ASCII)


# أسماء المحافظات بالعربية (مرجع شائع في الإعلام العربي)
TR_PROVINCE_AR: dict[int, str] = {
    1: "أضنة",
    2: "أديامان",
    3: "أفيون قره حصار",
    4: "آغري",
    5: "أماسيا",
    6: "أنقرة",
    7: "أنطاليا",
    8: "أرتفين",
    9: "أيدين",
    10: "باليك أسير",
    11: "بيليجيك",
    12: "بينغول",
    13: "بتليس",
    14: "بولو",
    15: "بوردور",
    16: "بورصة",
    17: "جنق قلعة",
    18: "تشانكيري",
    19: "تشوروم",
    20: "دنيزلي",
    21: "ديار بكر",
    22: "أدرنة",
    23: "إلازيغ",
    24: "أرزنجان",
    25: "أرضروم",
    26: "إسكي شهر",
    27: "غازي عنتاب",
    28: "جرسون",
    29: "غوميشخانة",
    30: "هاكاري",
    31: "هاتاي",
    32: "إسبرطة",
    33: "مرسين",
    34: "إسطنبول",
    35: "إزمير",
    36: "قارص",
    37: "قسطموني",
    38: "قيصري",
    39: "قركلريلي",
    40: "كيرشهر",
    41: "قوجاءلي",
    42: "قونية",
    43: "قتاهية",
    44: "ملاتيا",
    45: "مانيسا",
    46: "قهرمان ماراش",
    47: "ماردين",
    48: "مغلا",
    49: "موش",
    50: "نوشهر",
    51: "نيغدة",
    52: "أوردو",
    53: "ريزة",
    54: "صقارية",
    55: "سامسون",
    56: "سرت",
    57: "سينوب",
    58: "سيواس",
    59: "تكيرداغ",
    60: "توقات",
    61: "طرابزون",
    62: "تونجلي",
    63: "أورفا",
    64: "أوشاك",
    65: "وان",
    66: "يوزغات",
    67: "زونغولداك",
    68: "أقصراي",
    69: "بايبورت",
    70: "قرمان",
    71: "قره كلي",
    72: "باتمان",
    73: "شرناق",
    74: "بارتين",
    75: "أردهان",
    76: "إغدير",
    77: "يلوا",
    78: "كارابوك",
    79: "كليس",
    80: "عثمانية",
    81: "دوزجة",
}

def load_string_map_json(path: str) -> dict[str, str]:
    if not os.path.isfile(path):
        return {}
    with open(path, encoding="utf-8") as f:
        data = json.load(f)
    if not isinstance(data, dict):
        return {}
    return {str(k): str(v) for k, v in data.items()}


def turkish_lower(s: str) -> str:
    out: list[str] = []
    for ch in s:
        if ch == "İ":
            out.append("i")
        elif ch == "I":
            out.append("ı")
        else:
            out.append(ch.lower())
    return "".join(out)


def _turkish_word_to_arabic(word: str) -> str:
    """تعريب مقطع واحد: حروف تركية (ج ≠ ك، تش، ش، غ…) + جمعات شائعة (ey→ي…)."""
    w = turkish_lower(word)
    if not w:
        return ""
    parts: list[str] = []
    i = 0
    n = len(w)
    while i < n:
        three = w[i : i + 3] if i + 2 < n else ""
        if three == "iye":
            parts.append("يعي")
            i += 3
            continue
        two = w[i : i + 2] if i + 1 < n else ""
        if two == "ey":
            parts.append("ي")
            i += 2
            continue
        if two in ("uy", "oy"):
            parts.append("وي")
            i += 2
            continue
        if two == "ay":
            parts.append("ي")
            i += 2
            continue
        if two == "ya":
            parts.append("يا")
            i += 2
            continue
        if two == "yu":
            parts.append("يو")
            i += 2
            continue
        if two == "ou":
            parts.append("و")
            i += 2
            continue
        if two == "au":
            parts.append("أ")
            i += 2
            continue
        ch = w[i]
        if ch == "ç":
            parts.append("تش")
        elif ch == "ğ":
            parts.append("غ")
        elif ch == "ş":
            parts.append("ش")
        elif ch == "ö":
            parts.append("أو")
        elif ch == "ü":
            parts.append("و")
        elif ch == "ı":
            parts.append("ي")
        elif ch == "i":
            parts.append("ي")
        elif ch == "a":
            parts.append("ا")
        elif ch == "e":
            parts.append("ي")
        elif ch == "o":
            parts.append("و")
        elif ch == "u":
            parts.append("و")
        elif ch == "b":
            parts.append("ب")
        elif ch == "c":
            parts.append("ج")
        elif ch == "d":
            parts.append("د")
        elif ch == "f":
            parts.append("ف")
        elif ch == "g":
            parts.append("ك")
        elif ch == "h":
            parts.append("ه")
        elif ch == "j":
            parts.append("ج")
        elif ch == "k":
            parts.append("ك")
        elif ch == "l":
            parts.append("ل")
        elif ch == "m":
            parts.append("م")
        elif ch == "n":
            parts.append("ن")
        elif ch == "p":
            parts.append("ب")
        elif ch == "q":
            parts.append("ق")
        elif ch == "r":
            parts.append("ر")
        elif ch == "s":
            parts.append("س")
        elif ch == "t":
            parts.append("ت")
        elif ch == "v":
            parts.append("ف")
        elif ch == "w":
            parts.append("و")
        elif ch == "y":
            parts.append("ي")
        elif ch == "z":
            parts.append("ز")
        elif ch == "x":
            parts.append("كس")
        elif ch.isdigit():
            parts.append(ch)
        else:
            parts.append(ch)
        i += 1
    return "".join(parts)


def turkish_place_name_to_arabic(text: str) -> str:
    if not text or not text.strip():
        return ""
    chunks: list[str] = []
    for raw in text.strip().split():
        pieces: list[str] = []
        for j, piece in enumerate(raw.split("-")):
            if j:
                pieces.append("-")
            if piece:
                pieces.append(_turkish_word_to_arabic(piece))
        chunks.append("".join(pieces))
    return " ".join(chunks)


def resolve_district_ar(
    did: int,
    d_tr: str,
    pid: int,
    overrides: dict[str, str],
) -> str:
    sid = str(did)
    if sid in overrides:
        return overrides[sid]
    name = (d_tr or "").strip()
    if name == "Merkez":
        par = TR_PROVINCE_AR.get(pid) or turkish_place_name_to_arabic(name)
        return f"مركز {par}"
    return turkish_place_name_to_arabic(name)


def resolve_neighborhood_ar(hid: int, h_tr: str, overrides: dict[str, str]) -> str:
    sid = str(hid)
    if sid in overrides:
        return overrides[sid]
    return turkish_place_name_to_arabic(h_tr)


def php_str(s: str) -> str:
    s = (s or "").replace("\\", "\\\\").replace("'", "\\'")
    return f"'{s}'"


def uniq_match_names(*parts: str) -> list[str]:
    seen: dict[str, bool] = {}
    out: list[str] = []
    for p in parts:
        t = (p or "").strip()
        if len(t) < 2:
            continue
        if t not in seen:
            seen[t] = True
            out.append(t)
    return out


def download_if_needed(name: str) -> str:
    path = os.path.join(DATA_DIR, name)
    if os.path.isfile(path) and os.path.getsize(path) > 0:
        return path
    os.makedirs(DATA_DIR, exist_ok=True)
    url = RAW_BASE + name
    print("Downloading", url, file=sys.stderr)
    req = urllib.request.Request(url, headers={"User-Agent": "aalenha-region-builder/1.0"})
    with urllib.request.urlopen(req, timeout=120) as resp:
        data = resp.read()
    with open(path, "wb") as f:
        f.write(data)
    return path


def load_json(name: str) -> list | dict:
    path = download_if_needed(name)
    with open(path, "rb") as f:
        raw = f.read()
    for enc in ("utf-8", "utf-8-sig"):
        try:
            return json.loads(raw.decode(enc))
        except (UnicodeDecodeError, json.JSONDecodeError):
            continue
    raise RuntimeError(f"Cannot decode JSON: {path}")


def write_ad_regions_tr() -> None:
    provinces = load_json("provinces.json")
    districts = load_json("districts.json")
    neighborhoods = load_json("neighborhoods.json")

    if not isinstance(provinces, list):
        raise RuntimeError("provinces.json must be a list")

    by_province: dict[int, list] = defaultdict(list)
    for d in districts:
        by_province[int(d["provinceId"])].append(d)

    by_district: dict[int, list] = defaultdict(list)
    for n in neighborhoods:
        by_district[int(n["districtId"])].append(n)

    for pid in by_province:
        by_province[pid].sort(key=lambda x: (x.get("name") or ""))

    for did in by_district:
        by_district[did].sort(key=lambda x: (x.get("name") or ""))

    provinces_sorted = sorted(provinces, key=lambda x: int(x["id"]))

    district_overrides = load_string_map_json(DISTRICT_OVERRIDES_JSON)
    neighborhood_overrides = load_string_map_json(NEIGHBORHOOD_OVERRIDES_JSON)

    with open(OUT, "w", encoding="utf-8") as out:
        out.write("<?php\n\n")
        out.write(
            "/**\n"
            " * تركيا: 81 il ← ilçe ← mahalle من بيانات turkey-geo-api (MIT).\n"
            " * https://github.com/onurusluca/turkey-geo-api\n"
            " *\n"
            " * توليد: python3 database/scripts/build_ad_regions_tr_from_turkey_geo_api.py\n"
            " */\n\n"
            "return [\n"
        )

        for p in provinces_sorted:
            pid = int(p["id"])
            tr_name = (p.get("name") or "").strip()
            en_name = turkish_ascii(tr_name)
            ar_name = TR_PROVINCE_AR.get(pid) or turkish_place_name_to_arabic(tr_name)
            state_code = f"TR-{pid:02d}"
            mn_state = uniq_match_names(tr_name, en_name, ar_name)

            mn_state_php = ",\n            ".join(php_str(x) for x in mn_state)

            out.write("    [\n")
            out.write(f"        'code' => {php_str(state_code)},\n")
            out.write(f"        'name_ar' => {php_str(ar_name)},\n")
            out.write(f"        'name_en' => {php_str(en_name)},\n")
            out.write(f"        'name_tr' => {php_str(tr_name)},\n")
            out.write("        'match_names' => [\n")
            out.write(f"            {mn_state_php},\n")
            out.write("        ],\n")
            out.write("        'cities' => [\n")

            dist_list = by_province.get(pid, [])
            for d in dist_list:
                did = int(d["id"])
                d_tr = (d.get("name") or "").strip()
                d_en = turkish_ascii(d_tr)
                d_ar = resolve_district_ar(did, d_tr, pid, district_overrides)
                city_code = f"TR-D-{did}"
                mn_city = uniq_match_names(d_tr, d_en, d_ar)

                mn_city_php = ",\n                ".join(php_str(x) for x in mn_city)

                out.write("            [\n")
                out.write(f"                'code' => {php_str(city_code)},\n")
                out.write(f"                'name_ar' => {php_str(d_ar)},\n")
                out.write(f"                'name_en' => {php_str(d_en)},\n")
                out.write(f"                'name_tr' => {php_str(d_tr)},\n")
                out.write("                'match_names' => [\n")
                out.write(f"                    {mn_city_php},\n")
                out.write("                ],\n")
                out.write("                'districts' => [\n")

                hoods = by_district.get(did, [])
                for nh in hoods:
                    hid = int(nh["id"])
                    h_tr = (nh.get("name") or "").strip()
                    h_en = turkish_ascii(h_tr)
                    h_ar = resolve_neighborhood_ar(hid, h_tr, neighborhood_overrides)
                    h_code = f"TR-M-{hid}"
                    mn_h = uniq_match_names(h_tr, h_en, h_ar)

                    mn_h_php = ",\n                        ".join(php_str(x) for x in mn_h)
                    out.write("                    [\n")
                    out.write(f"                        'code' => {php_str(h_code)},\n")
                    out.write(f"                        'name_ar' => {php_str(h_ar)},\n")
                    out.write(f"                        'name_en' => {php_str(h_en)},\n")
                    out.write(f"                        'name_tr' => {php_str(h_tr)},\n")
                    out.write("                        'match_names' => [\n")
                    out.write(f"                        {mn_h_php},\n")
                    out.write("                        ],\n")
                    out.write("                    ],\n")

                out.write("                ],\n")
                out.write("            ],\n")

            out.write("        ],\n")
            out.write("    ],\n")

        out.write("];\n")

    print("Wrote", OUT)


def main() -> None:
    write_ad_regions_tr()
    print(
        "Next: php artisan geo:rebuild-from-catalog",
        "(يُحدّث جدول geo_divisions من config/ad_regions.php).",
    )


if __name__ == "__main__":
    main()
