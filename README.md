# procon33_RemoteTravelers_php

## 実行環境
- PHP 7.4.3
- composer 2.3.8
- ubuntu 20.04
- mariadb 10.5.8

## 初期設定
```
git clone https://github.com/NIT-Anan-Procon/procon33_RemoteTravelers_php.git
cd procon33_RemoteTravelers_php
composer install
php artisan key:generate
php artisan migrate
```

## API一覧
- app/Http/Controllers/API/AccountController.php
  - signup
    - アカウント登録をするAPI
- app/Http/Controllers/API/TravelerController.php
  - addReport
    - 旅レポートを追加するAPI
  - finishTravel
    - 旅行を終了するAPI
  - startTravel
    - 旅行を開始するAPI
- app/Http/Controllers/API/CommonController.php
  - addComment
    - コメントを追加するAPI
  - checkTraveling
    - ユーザが旅行中かを確認するAPI
  - getAlbum
    - 過去の旅レポートを取得するAPI
  - getInfo
    - 旅行画面・閲覧画面に必要な情報を取得するAPI
  - saveLocation
    - 現在地を保存するAPI
  - updateInfo
    - 最後に取得してきたデータから更新された分のデータを取得するAPI
