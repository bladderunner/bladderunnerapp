<?php

return [
  'gcm' => [
      'priority' => 'normal',
      'dry_run' => false,
      'apiKey' => 'My_ApiKey',
  ],
  'fcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => 'My_ApiKey',
  ],
  'apn' => [
      'certificate' => __DIR__ . '/iosCertificates/apple_push_notification_production.pem',
      'passPhrase' => 'Purple@Demoaps324', //Optional
      'passFile' => __DIR__ . '/iosCertificates/yourKey.pem', //Optional
      'dry_run' => false
  ]
];
