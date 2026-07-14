#!/usr/bin/env python3
"""
يبني config/ad_regions_sy.php من ملفات GeoJSON في public/geo/syria-geojson.

- syr_admin1 + syr_admin2 + syr_admin3: الهيكل العام (محافظة → مركز OCHA → ناحية).
- syr_admin5: أحياء دمشق (64 حي) — ملف المستودع لا يكررها في admin3؛ ندمجها هنا.
  لمحافظة دمشق (SY01) نستبدل قائمة المراكز بأحياء admin5 (كل حي = «مركز» في الواجهة)
  مع مستوى ثالث ثابت للتحقق من ثلاثية الكتالوج. الأسماء العربية من DAMASCUS_HAY_AR
  (admin5 لا يحتوي NAME_ARA).
- syr_admin4_point: مرجع نقاط (لا يُستخدم حالياً لتفادي التكرار مع admin5).

تشغيل من جذر backend:
  python3 database/scripts/build_ad_regions_sy_from_geojson.py
"""
from __future__ import annotations

import json
import os
import sys
from collections import defaultdict

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
GEO_DIR = os.path.join(ROOT, "public", "geo", "syria-geojson")
OUT = os.path.join(ROOT, "config", "ad_regions_sy.php")

GOV_TR = {
    "SY01": "Şam ili",
    "SY02": "Halep ili",
    "SY03": "Şam kırsalı",
    "SY04": "Humus ili",
    "SY05": "Hama ili",
    "SY06": "Lazkiye ili",
    "SY07": "İdlib ili",
    "SY08": "Haseke ili",
    "SY09": "Deyrizor ili",
    "SY10": "Tartus ili",
    "SY11": "Rakka ili",
    "SY12": "Dera ili",
    "SY13": "Süveyda ili",
    "SY14": "Kuneytira ili",
}

# محافظة دمشق: CITY_PCODE في admin5 → استبدال مراكز admin2 بهذه الأحياء
ADMIN5_STATE_REPLACE = {
    "C1001": "SY01",
}

# syr_admin5 يوفّر HAY_EN فقط؛ الأسماء العربية مُدخَلة يدوياً للعرض عند locale=ar.
# المفتاح: HAY_PCODE — عند إضافة أحياء جديدة في GeoJSON أضف سطراً هنا.
DAMASCUS_HAY_AR: dict[str, str] = {
    "C10011010": "كفر سوسة",
    "C10011011": "جوبر",
    "C10011012": "القابون",
    "C10011013": "برزة",
    "C10011014": "حميش",
    "C10011015": "مساكن برزة",
    "C10011016": "بساتين أبو جرش",
    "C10011017": "التجارة",
    "C10011018": "العباسيين",
    "C10011019": "الزبلطاني",
    "C10011020": "الدويلعة",
    "C10011021": "الصناعة",
    "C10011022": "بستان النور",
    "C10011023": "اليرموك",
    "C10011024": "فلسطين",
    "C10011025": "الحجر الأسود",
    "C10011026": "العصاعة",
    "C10011027": "دمشق القديمة",
    "C10011028": "القصور",
    "C10011029": "الأضوية",
    "C10011030": "الديوانية",
    "C10011031": "الخطيب",
    "C10011032": "العقبة",
    "C10011033": "بغداد",
    "C10011034": "الأقصاب",
    "C10011035": "ركن الدين",
    "C10011036": "المهاجرين",
    "C10011037": "الصالحية",
    "C10011038": "الطلباني",
    "C10011039": "المزرعة",
    "C10011040": "الفيحاء",
    "C10011041": "الميسات",
    "C10011042": "عرنوس",
    "C10011043": "السلحية",
    "C10011044": "صروجة",
    "C10011045": "الروضة",
    "C10011046": "أبو رمانة",
    "C10011047": "المالكي",
    "C10011048": "البرامكة",
    "C10011049": "الحلبوني",
    "C10011050": "المرجة",
    "C10011051": "الشعلان",
    "C10011052": "جسر الأبيض",
    "C10011053": "أبو جرش",
    "C10011054": "الشاغور",
    "C10011055": "القنوات",
    "C10011056": "المجتهد",
    "C10011057": "الفحامة",
    "C10011058": "الزهرة",
    "C10011059": "الميدان",
    "C10011060": "حديقة تشرين",
    "C10011061": "الرابية",
    "C10011062": "وادي الرازي",
    "C10011063": "دمر البلد",
    "C10011064": "قاسيون",
    "C10011065": "مشروع دمر",
    "C10011066": "القصر الجمهوري",
    "C10011067": "غير معروف",
    "C10011068": "القدم",
    "C10011069": "نهر عائشة",
    "C10011070": "مطار المزة",
    "C10011071": "المزة",
    "C10011072": "القزاز",
    "C10011073": "باب مصلى",
}


def load_geojson(filename: str) -> list:
    path = os.path.join(GEO_DIR, filename)
    raw = open(path, "rb").read()
    for enc in ("utf-8", "utf-8-sig", "cp1256", "latin-1"):
        try:
            return json.loads(raw.decode(enc))["features"]
        except (UnicodeDecodeError, json.JSONDecodeError):
            continue
    raise RuntimeError(f"Cannot decode JSON: {path}")


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


def format_district_block(code: str, ar: str, en: str, tr: str, match_names: list[str]) -> str:
    mn_php = ",\n                        ".join(php_str(x) for x in match_names)
    return f"""                    [
                        'code' => {php_str(code)},
                        'name_ar' => {php_str(ar)},
                        'name_en' => {php_str(en)},
                        'name_tr' => {php_str(tr)},
                        'match_names' => [
                        {mn_php},
                        ],
                    ],"""


def format_city_block(
    city_code: str,
    ar: str,
    en: str,
    tr: str,
    match_names: list[str],
    district_blocks: list[str],
) -> str:
    mn2_php = ",\n                    ".join(php_str(x) for x in match_names)
    dist_joined = "\n".join(district_blocks)
    return f"""            [
                'code' => {php_str(city_code)},
                'name_ar' => {php_str(ar)},
                'name_en' => {php_str(en)},
                'name_tr' => {php_str(tr)},
                'match_names' => [
                    {mn2_php},
                ],
                'districts' => [
{dist_joined}
                ],
            ],"""


def build_damascus_from_admin5(hays: list[dict]) -> list[str]:
    """كل حي = مركز (مستوى 2) + مستوى ثالث واحد للتحقق."""
    blocks: list[str] = []
    for p in sorted(hays, key=lambda x: x.get("HAY_PCODE", "")):
        hay_pcode = (p.get("HAY_PCODE") or "").strip()
        hay_en = (p.get("HAY_EN") or "").strip()
        if not hay_pcode or not hay_en:
            continue
        if hay_pcode not in DAMASCUS_HAY_AR:
            print(
                f"Warning: missing DAMASCUS_HAY_AR entry for {hay_pcode!r} ({hay_en!r}); using English for name_ar.",
                file=sys.stderr,
            )
        hay_ar = (DAMASCUS_HAY_AR.get(hay_pcode) or "").strip() or hay_en
        leaf_code = hay_pcode + "-MAIN"
        # المستوى 2 و3: عربي للواجهة ar، إنجليزي للـ en/tr (لا تركية مخصصة لأحياء دمشق في المصدر)
        leaf_ar = hay_ar
        leaf_en = hay_en
        leaf_tr = hay_en
        mn = uniq_match_names(hay_ar, hay_en)
        dist = format_district_block(
            leaf_code,
            leaf_ar,
            leaf_en,
            leaf_tr,
            mn,
        )
        blocks.append(
            format_city_block(
                hay_pcode,
                hay_ar,
                hay_en,
                hay_en,
                mn,
                [dist],
            )
        )
    return blocks


def main() -> None:
    a1 = load_geojson("syr_admin1.geojson")
    a2 = load_geojson("syr_admin2.geojson")
    a3 = load_geojson("syr_admin3.geojson")
    a5 = load_geojson("syr_admin5.geojson")

    by_admin1: dict[str, list] = defaultdict(list)
    for feat in a2:
        p = feat["properties"]
        by_admin1[p["ADMIN1_COD"]].append(p)

    by_admin2: dict[str, list] = defaultdict(list)
    for feat in a3:
        p = feat["properties"]
        by_admin2[p["ADMIN2_COD"]].append(p)

    admin5_by_city: dict[str, list] = defaultdict(list)
    for feat in a5:
        p = feat["properties"]
        cp = (p.get("CITY_PCODE") or "").strip()
        if cp:
            admin5_by_city[cp].append(p)

    states_out: list[str] = []
    for feat in sorted(a1, key=lambda x: x["properties"].get("PCODE", "")):
        p1 = feat["properties"]
        code1 = p1["PCODE"]
        ar1 = (p1.get("NAME_ARA") or "").strip()
        en1 = (p1.get("NAME_EN") or "").strip()
        tr1 = GOV_TR.get(code1, en1)

        cities_block: list[str] = []

        replaced = False
        for city_pcode, st_code in ADMIN5_STATE_REPLACE.items():
            if st_code == code1 and city_pcode in admin5_by_city:
                cities_block = build_damascus_from_admin5(admin5_by_city[city_pcode])
                replaced = True
                break

        if not replaced:
            for p2 in sorted(by_admin1[code1], key=lambda x: (x.get("NAME_ARA") or x.get("PCODE"))):
                c2 = p2["PCODE"]
                ar2 = (p2.get("NAME_ARA") or "").strip()
                en2 = (p2.get("NAME_EN") or "").strip()
                mn2 = uniq_match_names(ar2, en2, (p2.get("NAM_EN_REF") or "").strip())

                dist_blocks: list[str] = []
                for p3 in sorted(by_admin2[c2], key=lambda x: (x.get("NAME_ARA") or x.get("PCODE"))):
                    c3 = p3["PCODE"]
                    ar3 = (p3.get("NAME_ARA") or "").strip()
                    en3 = (p3.get("NAME_EN") or "").strip()
                    tr3 = en3
                    mn3 = uniq_match_names(ar3, en3, (p3.get("NAM_EN_REF") or "").strip())
                    dist_blocks.append(format_district_block(c3, ar3, en3, tr3, mn3))

                cities_block.append(
                    format_city_block(c2, ar2, en2, en2, mn2, dist_blocks)
                )

        mn1 = uniq_match_names(ar1, en1, (p1.get("NAM_EN_REF") or "").strip())
        mn1_php = ",\n            ".join(php_str(x) for x in mn1)
        cities_joined = "\n".join(cities_block)
        states_out.append(
            f"""    [
        'code' => {php_str(code1)},
        'name_ar' => {php_str(ar1)},
        'name_en' => {php_str(en1)},
        'name_tr' => {php_str(tr1)},
        'match_names' => [
            {mn1_php},
        ],
        'cities' => [
{cities_joined}
        ],
    ],"""
        )

    body = "\n".join(states_out)
    content = f"""<?php

/**
 * سوريا: محافظات ← مراكز ← مناطق/أحياء من GeoJSON.
 * - admin1/2/3 (OCHA) للمحافظات الأخرى.
 * - admin5 لدمشق (C1001): كل حي كمركز مع مستوى ثالث ثابت (ضمن الحي) لأن admin2/admin3
 *   يعطيان خلية واحدة فقط لمدينة دمشق بينما الأحياء التفصيلية في syr_admin5.
 *   الأسماء العربية لأحياء دمشق من DAMASCUS_HAY_AR في database/scripts/build_ad_regions_sy_from_geojson.py.
 *
 * تحديث: python3 database/scripts/build_ad_regions_sy_from_geojson.py
 * المصدر: https://github.com/alahwa/Syria-GeoJson-Maps
 */
return [
{body}
];
"""
    with open(OUT, "w", encoding="utf-8") as f:
        f.write(content)
    print("Wrote", OUT)
    print(
        "Next: php artisan geo:rebuild-from-catalog",
        "(or: php artisan db:seed --class=GeoDivisionsSeeder)",
        "so /api/v1/districts/* lists all centers under each governorate.",
    )


if __name__ == "__main__":
    main()
