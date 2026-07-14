import 'package:a3lnha/core/data/currency_helper.dart';
import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/locale/app_translations.dart';
import 'package:a3lnha/helpers/numeral_helper.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/models/ad_model.dart';
import 'package:a3lnha/data/models/subcategory_model.dart';
import 'package:a3lnha/helpers/custom_fields_resolver.dart';
import 'package:a3lnha/data/services/ad_service.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/core/support/car_body_map_support.dart';
import 'package:a3lnha/presentation/widgets/car_body_map_widget.dart';
import 'package:a3lnha/presentation/widgets/auth/text_form_with_label.dart';
import 'package:a3lnha/presentation/widgets/map_location_picker.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_button.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';
import 'package:geolocator/geolocator.dart';
import 'package:geocoding/geocoding.dart';

class EditAdPage extends StatefulWidget {
  final String adUid;

  const EditAdPage({super.key, required this.adUid});

  @override
  State<EditAdPage> createState() => _EditAdPageState();
}

class _EditAdPageState extends State<EditAdPage> {
  final _titleController = TextEditingController();
  final _descriptionController = TextEditingController();

  AdModel? _ad;
  bool _loading = true;
  bool _saving = false;
  String? _currency;
  List<Map<String, dynamic>>? _categoryCustomFields;
  Map<String, dynamic> _customFields = {};
  final Map<String, TextEditingController> _customControllers = {};
  final Map<String, String> _customCurrency = {};
  final Map<String, bool> _customCheckboxes = {};
  final Map<String, bool> _customTbd = {};
  final Map<String, Map<String, dynamic>> _customCarBodyMaps = {};

  List<Map<String, dynamic>> get _activeFields {
    final list = _categoryCustomFields ?? [];
    return list.where((f) => f['is_active'] != false).toList();
  }

  List<Map<String, dynamic>> _normalizeFieldList(dynamic raw) {
    if (raw is! List) return const <Map<String, dynamic>>[];
    return raw
        .whereType<Map>()
        .map((e) => Map<String, dynamic>.from(e))
        .toList();
  }

  List<Map<String, dynamic>> _mergeFields(
    List<Map<String, dynamic>> categoryFields,
    List<Map<String, dynamic>> subcategoryFields,
  ) {
    final byId = <String, Map<String, dynamic>>{};
    for (final f in categoryFields) {
      final id = (f['id'] ?? f['key'] ?? '').toString().trim();
      if (id.isEmpty) continue;
      byId[id] = f;
    }
    for (final f in subcategoryFields) {
      final id = (f['id'] ?? f['key'] ?? '').toString().trim();
      if (id.isEmpty) continue;
      byId[id] = f; // الفئة الفرعية تتفوّق عند التعارض (مثل الويب)
    }
    return byId.values.toList();
  }

  String _getFieldLabel(Map<String, dynamic> field) {
    final label = field['label'];
    if (label == null) return (field['id'] ?? '').toString();
    if (label is Map) {
      final locale = AppLocale.current;
      return (label[locale] ?? label['ar'] ?? label['en'] ?? label['tr'] ?? '')
          .toString();
    }
    return label.toString();
  }

  String _getOptionLabel(dynamic option) {
    if (option == null) return '';
    if (option is Map) {
      final locale = AppLocale.current;
      return (option[locale] ?? option['ar'] ?? option['en'] ?? option['tr'] ?? '')
          .toString();
    }
    return option.toString();
  }

  String _getOptionValue(dynamic option) {
    if (option == null) return '';
    if (option is Map) {
      final locale = AppLocale.current;
      return (option[locale] ?? option['ar'] ?? option['en'] ?? option['tr'] ?? '')
          .toString();
    }
    return option.toString();
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void dispose() {
    _titleController.dispose();
    _descriptionController.dispose();
    for (final c in _customControllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    final response = await AdService.getMyAdDetails(widget.adUid);
    if (!mounted) return;
    if (response == null) {
      setState(() {
        _loading = false;
        _ad = null;
      });
      showToast(message: AppLocale.tr('ad_load_failed'));
      return;
    }
    final ad = response.ad;
    _ad = ad;
    _titleController.text = ad.title;
    _descriptionController.text = ad.description ?? '';
    _currency = ad.currency ?? 'SYP';
    if (ad.customFields != null && ad.customFields!.isNotEmpty) {
      _customFields = Map.from(ad.customFields!);
    }
    final categoryIdRaw = ad.category?['id'];
    final subcategoryIdRaw = ad.subcategory?['id'];
    final categoryId = categoryIdRaw is int
        ? categoryIdRaw
        : int.tryParse(categoryIdRaw?.toString() ?? '');
    final subcategoryId = subcategoryIdRaw is int
        ? subcategoryIdRaw
        : int.tryParse(subcategoryIdRaw?.toString() ?? '');

    final payloadCategoryFields =
        _normalizeFieldList(ad.category?['custom_fields']);
    final payloadSubcategoryFields =
        _normalizeFieldList(ad.subcategory?['custom_fields']);
    List<Map<String, dynamic>> mergedFields = _mergeFields(
      payloadCategoryFields,
      payloadSubcategoryFields,
    );

    if (categoryId != null) {
      final cat = await CategoryService.getCategory(
        categoryId,
        forceRefresh: true,
      );
      List<Map<String, dynamic>> networkCategoryFields = cat?.customFields
              ?.map((e) => Map<String, dynamic>.from(e))
              .toList() ??
          const <Map<String, dynamic>>[];

      List<Map<String, dynamic>> networkSubcategoryFields =
          const <Map<String, dynamic>>[];
      SubcategoryModel? sub;
      if (subcategoryId != null) {
        sub = await CategoryService.getSubcategory(subcategoryId);
        networkSubcategoryFields = sub?.customFields
                ?.map((e) => Map<String, dynamic>.from(e))
                .toList() ??
            const <Map<String, dynamic>>[];
      }

      final resolved = sub != null
          ? (sub.resolvedCustomFields ??
              CustomFieldsResolver.resolveForLeaf(
                leaf: sub,
                subcategoryById: {sub.id: sub},
                category: cat,
              ))
          : <Map<String, dynamic>>[];

      if (resolved.isNotEmpty) {
        _categoryCustomFields = resolved;
      } else if (networkCategoryFields.isEmpty && networkSubcategoryFields.isEmpty) {
        _categoryCustomFields = mergedFields;
      } else {
        mergedFields = _mergeFields(networkCategoryFields, networkSubcategoryFields);
        _categoryCustomFields = mergedFields;
      }
    } else {
      _categoryCustomFields = mergedFields;
    }

    if (mounted) {
      for (final f in _activeFields) {
            final fieldId = (f['id'] ?? '').toString();
            if (fieldId.isEmpty) continue;
            final type = f['type'] ?? 'text';
            final val = _customFields[fieldId];

            if (type == 'car_body_map') {
              _customCarBodyMaps[fieldId] = CarBodyMapSupport.normalizeValue(val);
            } else if (type == 'location') {
              final locMap = val is Map ? Map<String, dynamic>.from(val) : <String, dynamic>{};
              _customControllers[fieldId] = TextEditingController(
                text: (locMap['address'] ?? '').toString(),
              );
              _customControllers['${fieldId}_lat'] = TextEditingController(
                text: (locMap['latitude'] ?? locMap['lat'] ?? '').toString(),
              );
              _customControllers['${fieldId}_lng'] = TextEditingController(
                text: (locMap['longitude'] ?? locMap['lng'] ?? '').toString(),
              );
            } else if (type == 'number' && (f['show_currency'] == true)) {
              final isTbd = val is Map && (val['tbd'] == true || val['tbd'] == '1');
              if (isTbd) {
                _customTbd[fieldId] = true;
                _customControllers[fieldId] = TextEditingController(text: '');
                _customCurrency[fieldId] = 'SYP';
              } else if (val is Map) {
                final raw = (val['value'] ?? '').toString();
                final numVal = NumeralHelper.parseAmount(raw);
                _customControllers[fieldId] = TextEditingController(
                  text: numVal != null ? NumeralHelper.formatWithThousands(numVal) : raw,
                );
                _customCurrency[fieldId] = (val['currency'] ?? 'SYP').toString();
              } else {
                final raw = val?.toString() ?? '';
                final numVal = NumeralHelper.parseAmount(raw);
                _customControllers[fieldId] = TextEditingController(
                  text: numVal != null ? NumeralHelper.formatWithThousands(numVal) : raw,
                );
                _customCurrency[fieldId] = 'SYP';
              }
            } else if (type == 'checkbox') {
              _customCheckboxes[fieldId] = val == true || val == 1 || val == '1';
            } else {
              final controller = TextEditingController();
              if (val is Map && val.containsKey('value')) {
                controller.text = val['value']?.toString() ?? '';
              } else if (val != null) {
                controller.text = val.toString();
              }
              _customControllers[fieldId] = controller;
            }
      }
    }
    setState(() => _loading = false);
  }

  Future<void> _save() async {
    final title = _titleController.text.trim();
    final description = _descriptionController.text.trim();
    if (title.isEmpty) {
      showToast(message: AppLocale.tr('ad_title_required'));
      return;
    }
    if (description.isEmpty) {
      showToast(message: AppLocale.tr('ad_description_required'));
      return;
    }
    final customFields = <String, dynamic>{};
    for (final f in _activeFields) {
      final fieldId = (f['id'] ?? '').toString();
      if (fieldId.isEmpty) continue;
      final type = f['type'] ?? 'text';

      if (type == 'checkbox') {
        customFields[fieldId] = _customCheckboxes[fieldId] == true ? 1 : 0;
        continue;
      }
      if (type == 'car_body_map') {
        customFields[fieldId] =
            _customCarBodyMaps[fieldId] ?? CarBodyMapSupport.normalizeValue(null);
        continue;
      }
      if (type == 'location') {
        final latC = _customControllers['${fieldId}_lat'];
        final lngC = _customControllers['${fieldId}_lng'];
        final addrC = _customControllers[fieldId];
        final lat = (latC?.text.trim().isEmpty ?? true) ? null : NumeralHelper.parseAmount(latC!.text);
        final lng = (lngC?.text.trim().isEmpty ?? true) ? null : NumeralHelper.parseAmount(lngC!.text);
        customFields[fieldId] = {
          'latitude': lat,
          'longitude': lng,
          'address': addrC?.text?.trim() ?? '',
        };
        continue;
      }
      if (type == 'number' && (f['show_currency'] == true) && (_customTbd[fieldId] == true)) {
        customFields[fieldId] = {'tbd': true};
        continue;
      }
      final c = _customControllers[fieldId];
      if (c == null) continue;
      final v = c.text.trim();
      if (type == 'number' && (f['show_currency'] == true)) {
        customFields[fieldId] = {
          'value': v.isEmpty ? null : (NumeralHelper.parseFormattedAmount(v) ?? NumeralHelper.parseAmount(v) ?? v),
          'currency': _customCurrency[fieldId] ?? 'SYP',
        };
      } else if (type == 'number') {
        customFields[fieldId] = v.isEmpty ? null : (NumeralHelper.parseFormattedAmount(v) ?? NumeralHelper.parseAmount(v) ?? v);
      } else {
        customFields[fieldId] = v;
      }
    }
    // إرسال السعر الرئيسي من أول حقل رقم+عملة حتى يُحدَّث عمود price في الجدول
    num? mainPrice;
    String? mainCurrency;
    for (final entry in customFields.entries) {
      final val = entry.value;
      if (val is Map && val.containsKey('value') && val.containsKey('currency')) {
        final v = val['value'];
        if (v != null && v.toString().trim().isNotEmpty) {
          mainPrice = NumeralHelper.parseAmount(v.toString()) ?? (v is num ? v : null);
          mainCurrency = (val['currency'] ?? 'SYP').toString();
          break;
        }
      }
    }
    setState(() => _saving = true);
    final result = await AdService.updateAd(
      adUid: widget.adUid,
      title: title,
      description: description,
      price: mainPrice?.toDouble(),
      currency: mainCurrency ?? _currency,
      customFields: customFields.isNotEmpty ? customFields : null,
    );
    if (!mounted) return;
    setState(() => _saving = false);
    showToast(message: result.message ?? '');
    if (result.success) {
      context.pop();
    }
  }

  Widget _buildCustomField(Map<String, dynamic> field) {
    final id = (field['id'] ?? '').toString();
    final type = field['type'] ?? 'text';
    final label = _getFieldLabel(field);
    final isRequired = field['required'] == true;
    final labelText = isRequired ? '$label *' : label;

    if (type == 'textarea') {
      final c = _customControllers[id];
      if (c == null) return const SizedBox.shrink();
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: TextFormWithLabel(
          hintText: label,
          controller: c,
          labelText: labelText,
          maxlines: 4,
          keyboardType: TextInputType.multiline,
          obscureText: false,
        ),
      );
    }
    if (type == 'number') {
      final c = _customControllers[id];
      if (c == null) return const SizedBox.shrink();
      final showCurrency = field['show_currency'] == true;
      final allowTbd = field['allow_tbd'] == true;
      final isTbd = _customTbd[id] == true;
      if (showCurrency) {
        final currency = _customCurrency[id] ?? 'SYP';
        return Padding(
          padding: EdgeInsets.only(bottom: 12.h),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(labelText, style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500)),
              SizedBox(height: 8.h),
              if (allowTbd)
                Padding(
                  padding: EdgeInsets.only(bottom: 8.h),
                  child: CheckboxListTile(
                    value: isTbd,
                    onChanged: (v) {
                      setState(() {
                        _customTbd[id] = v ?? false;
                        if (v == true) c.clear();
                      });
                    },
                    title: Text(
                      AppLocale.tr('price_tbd'),
                      style: TextStyle(fontSize: 13.sp),
                    ),
                    contentPadding: EdgeInsets.zero,
                    controlAffinity: ListTileControlAffinity.leading,
                    dense: true,
                  ),
                ),
              Container(
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade400),
                  borderRadius: BorderRadius.circular(10.r),
                ),
                child: Row(
                  children: [
                    Expanded(
                      flex: 2,
                      child: TextFormField(
                        controller: c,
                        keyboardType: const TextInputType.numberWithOptions(decimal: true),
                        inputFormatters: [ThousandSeparatorInputFormatter(allowDecimal: true)],
                        decoration: InputDecoration(
                          hintText: label,
                          hintStyle: TextStyle(fontSize: 14.sp, color: Colors.grey),
                          border: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(horizontal: 14.w, vertical: 14.h),
                          filled: false,
                        ),
                        style: TextStyle(fontSize: 14.sp),
                      ),
                    ),
                    Container(
                      width: 1,
                      height: 36.h,
                      color: Colors.grey.shade300,
                    ),
                    Expanded(
                      child: DropdownButtonHideUnderline(
                        child: DropdownButtonFormField<String>(
                          value: currency,
                          decoration: const InputDecoration(
                            border: InputBorder.none,
                            contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          ),
                          isExpanded: true,
                          items: [
                            DropdownMenuItem(value: 'SYP', child: Text(CurrencyHelper.symbol('SYP'), style: TextStyle(fontSize: 14.sp))),
                            DropdownMenuItem(value: 'TRY', child: Text(CurrencyHelper.symbol('TRY'), style: TextStyle(fontSize: 14.sp))),
                            DropdownMenuItem(value: 'USD', child: Text(CurrencyHelper.symbol('USD'), style: TextStyle(fontSize: 14.sp))),
                            DropdownMenuItem(value: 'EUR', child: Text(CurrencyHelper.symbol('EUR'), style: TextStyle(fontSize: 14.sp))),
                          ],
                          onChanged: (v) {
                            if (v != null) setState(() => _customCurrency[id] = v);
                          },
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        );
      }
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: TextFormWithLabel(
          hintText: label,
          controller: c,
          labelText: labelText,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          obscureText: false,
          inputFormatters: [ThousandSeparatorInputFormatter(allowDecimal: true)],
        ),
      );
    }
    if (type == 'select') {
      final options = (field['options'] as List?) ?? [];
      String? currentVal;
      final c = _customControllers[id];
      if (c != null && c.text.isNotEmpty) currentVal = c.text;
      final validValues = options.map((opt) => _getOptionValue(opt)).toList();
      if (currentVal != null && currentVal.isNotEmpty && !validValues.contains(currentVal)) {
        currentVal = null;
      }
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: DropdownButtonFormField<String?>(
          value: currentVal,
          decoration: InputDecoration(
            labelText: labelText,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(8.r)),
          ),
          items: [
            DropdownMenuItem<String?>(value: null, child: Text(AppLocale.tr('select_option'))),
            ...options.map((opt) {
              final val = _getOptionValue(opt);
              return DropdownMenuItem<String?>(
                value: val.isEmpty ? null : val,
                child: Text(_getOptionLabel(opt)),
              );
            }),
          ],
          onChanged: (v) {
            final ctrl = _customControllers[id];
            if (ctrl != null) ctrl.text = v ?? '';
          },
        ),
      );
    }
    if (type == 'checkbox') {
      final checked = _customCheckboxes[id] ?? false;
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: Row(
          children: [
            Checkbox(
              value: checked,
              onChanged: (v) {
                setState(() => _customCheckboxes[id] = v ?? false);
              },
              activeColor: AppColors.darkBlue,
            ),
            Expanded(child: Text(labelText, style: TextStyle(fontSize: 14.sp))),
          ],
        ),
      );
    }
    if (type == 'date') {
      final c = _customControllers[id];
      if (c == null) return const SizedBox.shrink();
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(labelText, style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500)),
            SizedBox(height: 8.h),
            InkWell(
              onTap: () async {
                final now = DateTime.now();
                DateTime? initial;
                if (c.text.trim().isNotEmpty) {
                  initial = DateTime.tryParse(c.text.trim());
                }
                final picked = await showDatePicker(
                  context: context,
                  initialDate: initial ?? now,
                  firstDate: DateTime(now.year - 10),
                  lastDate: DateTime(now.year + 30),
                );
                if (picked != null) {
                  c.text =
                      '${picked.year.toString().padLeft(4, '0')}-${picked.month.toString().padLeft(2, '0')}-${picked.day.toString().padLeft(2, '0')}';
                  if (mounted) setState(() {});
                }
              },
              child: Container(
                width: double.infinity,
                padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.grey.shade400),
                  borderRadius: BorderRadius.circular(10.r),
                ),
                child: Row(
                  children: [
                    Icon(Icons.calendar_today, size: 20.sp, color: AppColors.darkBlue),
                    SizedBox(width: 8.w),
                    Expanded(
                      child: Text(
                        c.text.trim().isEmpty ? label : c.text.trim(),
                        style: TextStyle(
                          fontSize: 14.sp,
                          color: c.text.trim().isEmpty ? Colors.grey : Colors.black,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      );
    }
    if (type == 'car_body_map') {
      return CarBodyMapWidget(
        label: labelText,
        initialValue: _customCarBodyMaps[id],
        onChanged: (value) {
          setState(() => _customCarBodyMaps[id] = value);
        },
      );
    }
    if (type == 'location') {
      final addrC = _customControllers[id];
      final latC = _customControllers['${id}_lat'];
      final lngC = _customControllers['${id}_lng'];
      if (addrC == null || latC == null || lngC == null) return const SizedBox.shrink();
      final hasLocation = latC.text.trim().isNotEmpty && lngC.text.trim().isNotEmpty;
      return Padding(
        padding: EdgeInsets.only(bottom: 12.h),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(labelText, style: TextStyle(fontSize: 14.sp, fontWeight: FontWeight.w500)),
            SizedBox(height: 8.h),
            OutlinedButton.icon(
              onPressed: () => _pickMyLocation(id),
              icon: Icon(Icons.my_location, size: 20.sp, color: AppColors.darkBlue),
              label: Text(AppLocale.tr('use_my_location')),
              style: OutlinedButton.styleFrom(
                foregroundColor: AppColors.darkBlue,
                side: BorderSide(color: AppColors.darkBlue),
              ),
            ),
            SizedBox(height: 12.h),
            InlineMapPicker(
              initialLat: double.tryParse(latC.text.trim()),
              initialLng: double.tryParse(lngC.text.trim()),
              height: 220.h,
              onLocationSelected: (lat, lng, address) {
                latC.text = lat.toString();
                lngC.text = lng.toString();
                addrC.text = address;
                if (mounted) setState(() {});
              },
            ),
            SizedBox(height: 8.h),
            Text(
              hasLocation
                  ? '${AppLocale.tr('location_determined')}${addrC.text.trim().isNotEmpty ? ': ${addrC.text}' : ' (${latC.text}, ${lngC.text})'}'
                  : AppLocale.tr('location_not_determined'),
              style: TextStyle(fontSize: 12.sp, color: Colors.grey[600]),
            ),
            SizedBox(height: 8.h),
            TextFormWithLabel(
              hintText: AppLocale.tr('address_hint'),
              controller: addrC,
              labelText: AppLocale.tr('address_hint'),
              keyboardType: TextInputType.streetAddress,
              obscureText: false,
            ),
          ],
        ),
      );
    }
    // text (default)
    final c = _customControllers[id];
    if (c == null) return const SizedBox.shrink();
    return Padding(
      padding: EdgeInsets.only(bottom: 12.h),
      child: TextFormWithLabel(
        hintText: label,
        controller: c,
        labelText: labelText,
        keyboardType: TextInputType.text,
        obscureText: false,
        maxlines: 1,
      ),
    );
  }

  Future<void> _pickMyLocation(String fieldId) async {
    final latC = _customControllers['${fieldId}_lat'];
    final lngC = _customControllers['${fieldId}_lng'];
    final addrC = _customControllers[fieldId];
    if (latC == null || lngC == null || addrC == null) return;
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      showToast(message: AppLocale.tr('location_error'));
      return;
    }
    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied || permission == LocationPermission.deniedForever) {
      showToast(message: AppLocale.tr('location_permission_denied'));
      return;
    }
    if (!mounted) return;
    showToast(message: AppLocale.tr('getting_location'));
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.medium,
      );
      latC.text = position.latitude.toString();
      lngC.text = position.longitude.toString();
      try {
        final placemarks = await placemarkFromCoordinates(position.latitude, position.longitude);
        if (placemarks.isNotEmpty) {
          final p = placemarks.first;
          final parts = [
            p.street,
            p.subLocality,
            p.locality,
            p.administrativeArea,
            p.country,
          ].where((e) => e != null && e.toString().trim().isNotEmpty).toList();
          addrC.text = parts.join(', ');
        }
      } catch (_) {}
      if (mounted) setState(() {});
      showToast(message: AppLocale.tr('location_determined'));
    } catch (e) {
      showToast(message: AppLocale.tr('location_error'));
    }
  }


  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('edit_ad')),
        body: Center(
          child: CircularProgressIndicator(color: AppColors.darkBlue),
        ),
      );
    }
    if (_ad == null && !_loading) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('edit_ad')),
        body: Center(
          child: Text(
            AppLocale.tr('ad_not_found'),
            style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
          ),
        ),
      );
    }

    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('edit_ad')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(20.w),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextFormWithLabel(
              labelText: AppLocale.tr('ad_title'),
              hintText: AppLocale.tr('ad_title_required'),
              controller: _titleController,
              keyboardType: TextInputType.text,
              obscureText: false,
              maxlines: 1,
            ),
            SizedBox(height: 16.h),
            TextFormWithLabel(
              labelText: AppLocale.tr('description'),
              hintText: '',
              controller: _descriptionController,
              keyboardType: TextInputType.multiline,
              obscureText: false,
              maxlines: 5,
            ),
            SizedBox(height: 24.h),
            if (_activeFields.isNotEmpty) ...[
              Text(
                AppLocale.tr('ad_details'),
                style: TextStyle(
                  fontSize: 14.sp,
                  fontWeight: FontWeight.w600,
                  color: AppColors.darkBlue,
                ),
              ),
              SizedBox(height: 12.h),
              ..._activeFields.map((f) => _buildCustomField(f)),
              SizedBox(height: 24.h),
            ],
            CustomButton(
              text: _saving ? AppLocale.tr('saving') : AppLocale.tr('save'),
              onTap: _saving ? () {} : _save,
              backgroundColor: AppColors.darkBlue,
              textColor: Colors.white,
            ),
          ],
        ),
      ),
    );
  }
}
