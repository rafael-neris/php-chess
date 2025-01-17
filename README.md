## PHP Chess

[![Latest Stable Version](https://poser.pugx.org/chesslablab/php-chess/v/stable)](https://packagist.org/packages/chesslablab/php-chess)
[![Build Status](https://app.travis-ci.com/chesslablab/php-chess.svg?branch=master)](https://app.travis-ci.com/github/chesslablab/php-chess)
[![License: GPL v3](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

A chess library for PHP.

### Install

Via composer:

    $ composer require chesslablab/php-chess

### Documentation

Read the latest docs [here](https://php-chess.readthedocs.io/en/latest/).

### Demo

PHP Chess is being used on [Redux Chess](https://github.com/chesslablab/redux-chess), which is a React chessboard connected to a [PHP Chess Server](https://github.com/chesslablab/chess-server). Check out [this demo](https://programarivm.github.io/demo-redux-chess).

> Please note the sandbox server might not be up and running all the time.

### Play Chess

```php
use Chess\Game;

$game = new Game();

$game->play('w', 'e4');
$game->play('b', 'e5');
```
The call to the `$game->play` method returns `true` or `false` depending on whether or not a chess move can be run on the board.

### Play Chess With an AI

Pass the `Game::MODE_AI` parameter when instantiating a `$game`:

```php
$game = new Game(Game::MODE_AI);

$game->play('w', 'e4');
$game->play('b', $game->response());
$game->play('w', 'e5');
$game->play('b', $game->response());
```

The AIs are stored in the [`model`](https://github.com/chesslablab/php-chess/tree/master/model) folder. The default is `a1.model`, if you want to play with a different AI pass it as a second parameter to the `Chess\Game` constructor:

```php
$game = new Game(Game::MODE_AI, 'a2.model');

$game->play('w', 'e4');
$game->play('b', $game->response());
$game->play('w', 'e5');
$game->play('b', $game->response());
```

### License

The GNU General Public License.

### Contributions

See the [contributing guidelines](https://github.com/chesslablab/php-chess/blob/master/CONTRIBUTING.md).

Happy learning and coding! Thank you, and keep it up.
