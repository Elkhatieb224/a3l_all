import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/saved_search_service.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/presentation/pages/account/saved_search_results_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:a3lnha/presentation/widgets/shared/show_toast.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class SavedSearchesPage extends StatefulWidget {
  const SavedSearchesPage({super.key});

  @override
  State<SavedSearchesPage> createState() => _SavedSearchesPageState();
}

class _SavedSearchesPageState extends State<SavedSearchesPage> {
  bool _loading = true;
  List<SavedSearchModel> _items = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final list = await SavedSearchService.getSavedSearches();
      if (!mounted) return;
      setState(() {
        _items = list;
        _loading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _loading = false);
      showToast(message: AppLocale.tr('failed'));
    }
  }

  Future<void> _delete(int id) async {
    final ok = await SavedSearchService.deleteSavedSearch(id);
    if (!mounted) return;
    showToast(message: ok ? AppLocale.tr('saved_search_deleted') : AppLocale.tr('failed'));
    if (ok) _load();
  }

  String _titleOf(SavedSearchModel item) {
    final name = (item.name ?? '').trim();
    if (name.isNotEmpty) return name;
    final query = (item.filters['search'] ?? '').toString().trim();
    if (query.isNotEmpty) return query;
    return '${AppLocale.tr('saved_search')} #${item.id}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('saved_searches')),
      body: _loading
          ? Center(child: CircularProgressIndicator(color: AppColors.darkBlue))
          : _items.isEmpty
              ? Center(
                  child: Text(
                    AppLocale.tr('no_saved_searches'),
                    style: TextStyle(fontSize: 14.sp, color: Colors.grey[600]),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _load,
                  child: ListView.builder(
                    padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
                    itemCount: _items.length,
                    itemBuilder: (context, index) {
                      final item = _items[index];
                      return Container(
                        margin: EdgeInsets.only(bottom: 10.h),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(10.r),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: ListTile(
                          onTap: () => context.push(SavedSearchResultsPage(savedSearchId: item.id)),
                          title: Text(_titleOf(item), maxLines: 1, overflow: TextOverflow.ellipsis),
                          subtitle: Text(
                            item.createdAt?.split('T').first ?? '',
                            style: TextStyle(fontSize: 12.sp),
                          ),
                          trailing: IconButton(
                            icon: Icon(Icons.delete_outline, color: Colors.red, size: 20.sp),
                            onPressed: () => _delete(item.id),
                          ),
                        ),
                      );
                    },
                  ),
                ),
    );
  }
}

