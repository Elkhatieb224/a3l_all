// Firebase options for com.aalenha.aalenha
// Android: google-services.json | iOS: GoogleService-Info.plist

import 'package:firebase_core/firebase_core.dart' show FirebaseOptions;
import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, kIsWeb, TargetPlatform;

class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError(
        'DefaultFirebaseOptions have not been configured for web.',
      );
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not supported for this platform.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyAk_aieWiDz6iyWL2wgDlgdobBKP__TkwI',
    appId: '1:146711910103:android:ea63d73de5ac0423820292',
    messagingSenderId: '146711910103',
    projectId: 'aalenha-91516',
    storageBucket: 'aalenha-91516.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyD-BMXmj42hhPorPSgQJsn9AwRFdzAL3zI',
    appId: '1:146711910103:ios:86132167f8da7fc0820292',
    messagingSenderId: '146711910103',
    projectId: 'aalenha-91516',
    storageBucket: 'aalenha-91516.firebasestorage.app',
    iosBundleId: 'com.aalenha.aalenha',
  );
}
