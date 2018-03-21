
# Wikitree

## 機能
現状はartisanコマンドのみの実装。詳細は以下参照。  
[artisanコマンド](app/Console/Commands/readme.md)

## SetUp

1. まず、ディレクトリを切って、本プロジェクトをcloneします。

```
mkdir develop
cd develop
git clone https://github.com/motojouya/wikitree.git
```

2. 次にLaraDockをcloneします。

```
git clone https://github.com/LaraDock/laradock.git
```

※Docker Tool boxの方は以下のブランチを取得してください。
```
git clone -b LaraDock-ToolBox https://github.com/LaraDock/laradock.git
```

3. 2でcloneしたlaradockディレクトリに入り設定を書き換えます。

```
cd laradock/
cp env-example .env
vi .env
```

```.env
APPLICATION=../wikitree/
```

4. docker-composeでビルドし、workspaceに入ります。

```
docker-compose up -d workspace
docker-compose exec workspace bash
```

5. コンテナ内でcomposerから必要モジュールをインストール

```
composer install
```

