# keikyu_location

京急線アプリで使用されているapiを使って走行位置･列車番号･番線･種別･direction･行先を返します。


kind.csv | position.csv = 種別 | 走行位置 を定義

[app-kq.net/api/train](https://app-kq.net/api/train) = 行先以外の全情報

[app-kq.net/api/trainTime](https://app-kq.net/api/trainTime) = 行先情報

行先情報を取得するうえで、```?train_no=1106A&direction=0&line_no=8201``` のようなパラメーターが必要です。

train_no･directionは```api/train```から、line_noは```position.csv```から
