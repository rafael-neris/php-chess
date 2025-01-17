#### `output(string $filepath, string $salt = '')`

Creates a PNG image from a particular `Chess\Board` object.

```php
use Chess\FEN\StringToBoard;
use Chess\Media\BoardToPng;

$board = (new StringToBoard('1rbq1rk1/p1b1nppp/1p2p3/8/1B1pN3/P2B4/1P3PPP/2RQ1R1K w - - bm Nf6+'))
    ->create();

$filename = (new BoardToPng($board))->output();
```

The code snippet above creates the `620a7d61dcf57.png` file.

![Figure 1](https://raw.githubusercontent.com/chesslablab/php-chess/master/tests/data/img/01_kaufman.png)
Figure 1. Position 1 of the Kaufman test

```php
$board = (new StringToBoard('1rbq1rk1/p1b1nppp/1p2p3/8/1B1pN3/P2B4/1P3PPP/2RQ1R1K w - - bm Nf6+'))
    ->create();

$filename = (new BoardToPng($board, $flip = true))->output();
```

The code snippet above creates the `620a7c618f281.png` file.

![Figure 2](https://raw.githubusercontent.com/chesslablab/php-chess/master/tests/data/img/01_kaufman_flip.png)
Figure 2. Position 1 of the Kaufman test
