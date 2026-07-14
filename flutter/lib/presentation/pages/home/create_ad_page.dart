import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/storage/token_storage.dart';
import 'package:a3lnha/data/models/category_model.dart';
import 'package:a3lnha/helpers/extentions.dart';
import 'package:a3lnha/data/services/category_service.dart';
import 'package:a3lnha/presentation/pages/auth/login_page.dart';
import 'package:a3lnha/presentation/pages/home/home_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';
import 'package:flutter_screenutil/flutter_screenutil.dart';

class CreateAdPage extends StatefulWidget {
  const CreateAdPage({super.key});

  @override
  State<CreateAdPage> createState() => _CreateAdPageState();
}

class _CreateAdPageState extends State<CreateAdPage> {
  List<CategoryModel> _categories = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    if (!TokenStorage.hasToken()) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        context.push(LoginPage());
        Navigator.of(context).pop();
      });
      return;
    }
    _loadCategories();
  }

  Future<void> _loadCategories() async {
    final list = await CategoryService.getCategories();
    if (mounted) {
      setState(() {
        _categories = list;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppbar(title: AppLocale.tr('create_ad')),
      body: Padding(
        padding: EdgeInsets.symmetric(vertical: 10.h, horizontal: 20.w),
        child: Column(
          children: [
            DrawerContent(
              isPostAd: true,
              categories: _categories,
              categoriesLoading: _loading,
            ),
          ],
        ),
      ),
    );
  }
}
