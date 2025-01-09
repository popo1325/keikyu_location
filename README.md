# keikyu_location

京急線アプリで使用されているapiを使って走行位置･列車番号･番線･種別･direction･行先を返します。

## 前提条件

kind.csv | position(1).csv = 種別 | 走行位置 を定義

[app-kq.net/api/train](https://app-kq.net/api/train) = 行先以外の全情報

[app-kq.net/api/trainTime](https://app-kq.net/api/trainTime) = 行先情報

行先情報を取得するうえで、```?train_no=1106A&direction=0&line_no=8201``` のようなパラメーターが必要です。

train_no･directionは```api/train```から、line_noは```position(1).csv```から

## [app-kq.net/api/train](https://app-kq.net/api/train)から取得できる情報

```
{
    "position": "D001",
    "train_no": "1304A",
    "platform": "0",
    "train_kind": "1",
    "direction": "1",
    "receive_datetime": "2025-01-09 13:40:36"
}
```

型は以上のとおりです。

```position``` は駅及び駅間一つ一つに割り振られている固有の値が表示されます。同じ駅であっても上り/下りで区別されています。

> 京急品川駅下り線の場合 : ED001

```train_no```は列車番号です。

```platform```は駅停車中に限り、番線を表示します。

> [!WARNING]
> 実際の番線順には準拠されてますが、京急川崎駅2･3番線のような一つの線路で2つの番線がある場合、同じ番線として扱われます。

## 動作の流れ

[app-kq.net/api/train](https://app-kq.net/api/train)