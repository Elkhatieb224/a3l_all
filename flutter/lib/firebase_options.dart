// File generated from google-services.json (com.example.a3lnha)

import 'dart:io' show Platform;

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (Platform.isAndroid) {
      return android;
    }
    throw UnsupportedError(
      'DefaultFirebaseOptions are not supported for this platform.\n'
      'Android is configured. For iOS/macOS run: gem install xcodeproj && flutterfire configure',
    );
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyAk_aieWiDz6iyWL2wgDlgdobBKP__TkwI',
    appId: '1:146711910103:android:fd0308f5b3db7067820292',
    messagingSenderId: '146711910103',
    projectId: 'aalenha-91516',
    storageBucket: 'aalenha-91516.firebasestorage.app',
  );

}