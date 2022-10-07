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

## ルーティング一覧
```routes/api.php```に記述
- POST /api/common/add-comment
  - コメントを追加する
- GET /api/common/check-traveling
  - 旅行中かどうかを確認する
- GET /api/common/get-info
  - 旅行情報を取得する
- POST /api/common/save-location
  - 現在地・行き先を保存する
- POST /api/common/update-info
  - 更新された旅行情報を取得する
- GET /api/common/get-album
  - 過去の旅レポートを取得する
- POST /api/traveler/add-report
  - 旅レポートを追加する
- POST /api/traveler/finish-travel
  - 旅を終了する
- POST /api/traveler/start-travel
  - 旅を開始する
- POST /api/user/signup
  - ユーザー登録する

## マイグレーション一覧
- database/migrations/2022_08_17_121618_create_accounts_table.php
    - それぞれのアカウントのuser_idや旅行に関する情報の最終更新日時を管理するテーブル
- database/migrations/2022_08_19_002916_create_locations_table.php
    - 旅行者の現在の位置情報、閲覧者に提案された行き先の位置情報をtravel_idと紐づけて管理するテーブル
- database/migrations/2022_08_19_002940_create_travels_table.php
    - 旅行に参加するユーザを管理するテーブル
- database/migrations/2022_08_19_003008_create_comments_table.php
    - 旅行に関するコメントを管理するテーブル
- database/migrations/2022_08_19_003028_create_reports_table.php
    - 旅行時の旅レポートを管理するテーブル
- database/migrations/2022_08_19_003051_create_situations_table.php
    - 旅行者の現在の状況を管理するテーブル

## モデル一覧
- app/Models/Account.php
    - アカウントの情報を管理するモデル
- app/Models/Comment.php
    - 旅行に関するコメントを管理するモデル
- app/Models/Location.php
    - 旅行者の現在の位置情報、閲覧者に提案された行き先の位置情報を管理するモデル
- app/Models/Report.php
    - 旅行時の旅レポートを管理するモデル
- app/Models/Situation.php
    - 旅行者の現在の状況を管理するモデル
- app/Models/Travel.php
    - 旅行に参加するユーザを管理するモデル

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
