import 'package:a3lnha/core/locale/app_locale.dart';
import 'package:a3lnha/core/styles/colors.dart';
import 'package:a3lnha/data/services/legal_service.dart';
import 'package:a3lnha/presentation/pages/legal/legal_content_page.dart';
import 'package:a3lnha/presentation/widgets/shared/custom_appbar.dart';
import 'package:flutter/material.dart';

class TermsPage extends StatefulWidget {
  const TermsPage({super.key});

  @override
  State<TermsPage> createState() => _TermsPageState();
}

class _TermsPageState extends State<TermsPage> {
  String? _content;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final content = await LegalService.getTermsContent();
    if (mounted) {
      setState(() {
        _content = content;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return Scaffold(
        appBar: CustomAppbar(title: AppLocale.tr('terms_title')),
        body: Center(
          child: CircularProgressIndicator(color: AppColors.darkBlue),
        ),
      );
    }
    return LegalContentPage(
      title: AppLocale.tr('terms_title'),
      subtitle: AppLocale.tr('terms_subtitle'),
      content: _content,
    );
  }
}
