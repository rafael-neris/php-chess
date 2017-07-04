<?php
namespace PGNChess;

use PGNChess\PGN;
use PGNChess\Squares;
use PGNChess\Piece\AbstractPiece;
use PGNChess\Piece\Piece;
use PGNChess\Piece\Bishop;
use PGNChess\Piece\King;
use PGNChess\Piece\Knight;
use PGNChess\Piece\Pawn;
use PGNChess\Piece\Queen;
use PGNChess\Piece\Rook;

/**
 * Class that represents a chess board. This is basically a container of chess
 * pieces that are constantly being updated/removed as players run their moves
 * on the board.
 *
 * @author Jordi Bassagañas <info@programarivm.com>
 * @link https://programarivm.com
 * @license MIT
 */
class Board extends \SplObjectStorage
{
    /**
     * @var stdClass
     */
    private $status;

    /**
     * Constructor.
     *
     * @param null|array $pieces
     */
    public function __construct(array $pieces=null)
    {
        if (empty($pieces))
        {
            $this->attach(new Rook(PGN::COLOR_WHITE, 'a1'));
            $this->attach(new Knight(PGN::COLOR_WHITE, 'b1'));
            $this->attach(new Bishop(PGN::COLOR_WHITE, 'c1'));
            $this->attach(new Queen(PGN::COLOR_WHITE, 'd1'));
            $this->attach(new King(PGN::COLOR_WHITE, 'e1'));
            $this->attach(new Bishop(PGN::COLOR_WHITE, 'f1'));
            $this->attach(new Knight(PGN::COLOR_WHITE, 'g1'));
            $this->attach(new Rook(PGN::COLOR_WHITE, 'h1'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'a2'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'b2'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'c2'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'd2'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'e2'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'f2'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'g2'));
            $this->attach(new Pawn(PGN::COLOR_WHITE, 'h2'));
            $this->attach(new Rook(PGN::COLOR_BLACK, 'a8'));
            $this->attach(new Knight(PGN::COLOR_BLACK, 'b8'));
            $this->attach(new Bishop(PGN::COLOR_BLACK, 'c8'));
            $this->attach(new Queen(PGN::COLOR_BLACK, 'd8'));
            $this->attach(new King(PGN::COLOR_BLACK, 'e8'));
            $this->attach(new Bishop(PGN::COLOR_BLACK, 'f8'));
            $this->attach(new Knight(PGN::COLOR_BLACK, 'g8'));
            $this->attach(new Rook(PGN::COLOR_BLACK, 'h8'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'a7'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'b7'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'c7'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'd7'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'e7'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'f7'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'g7'));
            $this->attach(new Pawn(PGN::COLOR_BLACK, 'h7'));
        }
        else
        {
            foreach($pieces as $piece)
            {
                $this->attach($piece);
            }
        }

        $this->status = (object) [
            'turn' => null,
            'squares' => null,
            'space' => (object) [
                PGN::COLOR_WHITE => null,
                PGN::COLOR_BLACK => null
            ],
            'attack' => (object) [
                PGN::COLOR_WHITE => null,
                PGN::COLOR_BLACK => null
            ],
            'previousMove' => (object) [
                PGN::COLOR_WHITE => (object) [
                    'identity' => null,
                    'position' => (object) [
                        'current' => null,
                        'next' => null
                    ]
                ],
                PGN::COLOR_BLACK => (object) [
                    'identity' => null,
                    'position' => (object) [
                        'current' => null,
                        'next' => null
                    ]
                ]
            ]
        ];

        $this->updateStatus();
    }

    /**
     * Gets the current board's status.
     *
     * @return stdClass
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Updates the board's status. This method is run every time a piece is  moved.
     * First of all, updates the current player's turn. Then computes the board's
     * square statistics as well as the space/attack properties. It also updates
     * the previous move of both players, which is specially useful in order to
     * implement the pawns' en passant rule. Finally, keeps track of the fact that
     * a king can't castle once it has been moved; on the other hand, if the king's
     * long castling rook is moved then it won't be able to castle long according to
     * chess rules. And the same thing goes for the other rook: if it is moved,
     * then the king is not allowed to castle short.
     *
     * @link https://en.wikipedia.org/wiki/En_passant
     * @link https://en.wikipedia.org/wiki/Castling
     *
     * @see Board::pieceIsMoved(Piece $piece)
     *
     * @param PGNChess\Piece $piece
     *
     * @return PGNChess\Board
     */
    private function updateStatus($piece=null)
    {
        // (1) current player's turn
        $this->status->turn === PGN::COLOR_WHITE
            ? $this->status->turn = PGN::COLOR_BLACK
            : $this->status->turn = PGN::COLOR_WHITE;

        // (2) compute square statistics and send them (flat data) to all pieces
        $this->status->squares = Squares::stats(iterator_to_array($this, false));
        AbstractPiece::setSquares($this->status->squares);

        // (3) space and attack properties
        $this->status->space = $this->space();
        $this->status->attack = $this->attack();

        // (4) compute previous moves and send them (flat data) to all pieces
        if (isset($piece))
        {
            $this->status->previousMove->{$piece->getColor()}->identity = $piece->getIdentity();
            $this->status->previousMove->{$piece->getColor()}->position = $piece->getMove()->position;
            AbstractPiece::setPreviousMove($this->status->previousMove);
        }

        // (5) finally, update the king's castling property
        if (isset($piece) && $piece->getMove()->type === PGN::MOVE_TYPE_KING)
        {
            $piece->updateCastling();
        }
        elseif
        (
            isset($piece) &&
            $piece->getMove()->type === PGN::MOVE_TYPE_PIECE &&
            $piece->getIdentity() === PGN::PIECE_ROOK
        )
        {
            $king = $this->getPiece($piece->getColor(), PGN::PIECE_KING);
            $piece->updateCastling($king); // king passed by reference
        }
    }

    /**
     * Picks a piece to be moved, prioritizing the matching of the less ambiguous
     * one according to the PGN format. It returns the first available piece
     * that matches the criteria.
     *
     * @param stdClass $move
     *
     * @return array The piece(s) matching the PGN move; otherwise null.
     *
     * @throws \InvalidArgumentException
     */
    private function pickPieceToMove(\stdClass $move)
    {
        $found = [];
        $pieces = $this->getPiecesByColor($move->color);
        foreach ($pieces as $piece)
        {
            if ($piece->getIdentity() === $move->identity)
            {
                switch($piece->getIdentity())
                {
                    // the king is a non-ambiguous piece (there's only one)
                    case PGN::PIECE_KING:
                        $piece->setMove($move);
                        return [$piece];
                        break;

                    // the rest of pieces are potentially ambiguous and need to be disambiguated.
                    default:
                        if (preg_match("/{$move->position->current}/", $piece->getPosition()->current))
                        {
                            $piece->setMove($move);
                            $found[] = $piece;
                        }
                        break;
                }
            }
        }
        if (empty($found))
        {
            throw new \InvalidArgumentException(
                "This piece does not exist on the board: {$move->color} {$move->identity} on {$move->position->current}"
            );
        }
        else
        {
            return $found;
        }
    }

    /**
     * Runs a chess move on the board.
     *
     * Note that there are 3 different types of moves:
     *
     *      (1) kingIsMoved
     *      (2) castle
     *      (3) pieceIsMoved
     *
     * In all cases, you have to first pick the piece you want to move by calling
     * the pickPieceToMove(\stdClass $move) method -- which expects as an input
     * the objectized counterpart of a valid move in PGN notation. If it is the case
     * that the piece can be moved according to chess rules, the move will be run
     * and the chess board will be updated accordingly.
     *
     * @see PGN::objectizeMove($color, $pgn)
     *
     * @param stdClass $move
     *
     * @return boolean true if the move is successfully run; otherwise false
     */
    public function play(\stdClass $move)
    {
        $pieces = $this->pickPieceToMove($move);
        // the piece is disambiguated by picking the movable one from the array of ambiguous pieces.
        if (count($pieces) > 1)
        {
            foreach ($pieces as $piece)
            {
                if ($piece->isMovable() && !$this->isCheck($piece))
                {
                    return $this->pieceIsMoved($piece);
                }
            }
        }
        // the current piece is not ambiguous (there's only one in the $pieces array)
        elseif (count($pieces) == 1 && current($pieces)->isMovable() && !$this->isCheck(current($pieces)))
        {
            $piece = current($pieces);
            switch($piece->getMove()->type)
            {
                case PGN::MOVE_TYPE_KING:
                    return $this->kingIsMoved($piece);
                    break;

                case PGN::MOVE_TYPE_KING_CASTLING_SHORT:
                    if (
                        $piece->getCastling()->{PGN::CASTLING_SHORT}->canCastle &&
                        !(in_array(PGN::castling($piece->getColor())
                            ->{PGN::PIECE_KING}
                            ->{PGN::CASTLING_SHORT}
                            ->freeSquares
                            ->f,
                            $this->status->space->{$piece->getOppositeColor()})
                        ) &&
                        !(in_array(PGN::castling($piece->getColor())
                            ->{PGN::PIECE_KING}
                            ->{PGN::CASTLING_SHORT}
                            ->freeSquares
                            ->g,
                            $this->status->space->{$piece->getOppositeColor()})
                        )
                    )
                    {
                        return $this->castle($piece);
                    }
                    else
                    {
                        return false;
                    }
                    break;

                case PGN::MOVE_TYPE_KING_CASTLING_LONG:
                    if (
                        $piece->getCastling()->{PGN::CASTLING_LONG}->canCastle &&
                        !(in_array(PGN::castling($piece->getColor())
                            ->{PGN::PIECE_KING}
                            ->{PGN::CASTLING_LONG}
                            ->freeSquares
                            ->b,
                            $this->status->space->{$piece->getOppositeColor()})
                        ) &&
                        !(in_array(PGN::castling($piece->getColor())
                            ->{PGN::PIECE_KING}
                            ->{PGN::CASTLING_LONG}
                            ->freeSquares
                            ->c,
                            $this->status->space->{$piece->getOppositeColor()})
                        ) &&
                        !(in_array(PGN::castling($piece->getColor())
                            ->{PGN::PIECE_KING}
                            ->{PGN::CASTLING_LONG}
                            ->freeSquares
                            ->d,
                            $this->status->space->{$piece->getOppositeColor()})
                        )
                    )
                    {
                        return $this->castle($piece);
                    }
                    else
                    {
                        return false;
                    }
                    break;

                default:
                    return $this->pieceIsMoved($piece);
                    break;
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Moves the king.
     *
     * @see Board::space()
     *
     * @param King $king
     *
     * @return boolean true if the king captured the piece; otherwise false
     */
    private function kingIsMoved(King $king)
    {
        switch ($king->getMove()->type)
        {
            // the king can be moved if it's outside the scope of the opponent's space.
            case PGN::MOVE_TYPE_KING:
                if (!in_array($king->getMove()->position->next,
                    $this->status->space->{$king->getOppositeColor()}))
                {
                    return $this->pieceIsMoved($king);
                }
                else
                {
                    return false;
                }
                break;

            /*
            * This is like going to the future to see what will happen in the next
            * move in order to take a decision accordingly. It "forks" the current board
            * and simulates the king's capture move on it. Here is the idea actually being
            * implemented: (1) the piece to be captured is removed from the forked board,
            * and (2) then the king moves to the square where the captured piece should be
            * standing. Following this logical sequence, if it turns out that the king is
            * on a square controlled by the opponent, the king can't capture the piece.
            * This way we can reuse the method implementing a normal king's move.
            */
            case PGN::MOVE_TYPE_KING_CAPTURES:
                $that = clone $this;
                $capturedPiece = $that->getPieceByPosition($king->getMove()->position->next);
                $that->detach($capturedPiece);
                return $that->kingIsMoved($king);
                break;
        }
    }

    /**
     * Castles the king.
     *
     * @param PGNChess\Piece\King $king
     *
     * @return boolean true if the castling is successfully run; otherwise false.
     */
    private function castle(King $king)
    {
        try
        {
            // get castling rook
            $rook = $king->getCastlingRook(iterator_to_array($this, false));
            switch(empty($rook))
            {
                case false:
                    // move the king
                    $kingsNewPosition = $king->getPosition();
                    $kingsNewPosition->current = PGN::castling($king->getColor())
                        ->{PGN::PIECE_KING}
                        ->{$king->getMove()->pgn}
                        ->position
                        ->next;
                    $king->setPosition($kingsNewPosition)->setIsCastled();
                    $this->pieceIsMoved($king);
                    // move the king's castling rook
                    $rooksNewPosition = $rook->getPosition();
                    $rooksNewPosition->current = PGN::castling($king->getColor())
                        ->{PGN::PIECE_ROOK}
                        ->{$king->getMove()->pgn}
                        ->position
                        ->next;
                    $rook->setMove((object) [
                        'type' => $king->getMove()->type,
                        'isCapture' => $king->getMove()->isCapture,
                        'position' => (object) [
                            'next' => $rooksNewPosition->current
                        ]
                    ]);
                    $this->pieceIsMoved($rook);
                    return true;
                    break;

                case true:
                    return false;
                    break;
            }
        }
        catch (\Exception $e)
        {
            // TODO log exception...
            return false;
        }
    }

    /**
     * Moves a piece.
     *
     * @param PGNChess\Piece\Piece $piece
     *
     * @return boolean true if the move is successfully performed; otherwise false
     */
    private function pieceIsMoved(Piece $piece)
    {
        try
        {
            // move piece
            $pieceClass = new \ReflectionClass(get_class($piece));
            $this->detach($piece);
            $this->attach($pieceClass->newInstanceArgs([
                $piece->getColor(),
                $piece->getMove()->position->next])
            );
            // remove the captured piece from the board, if any
            if($piece->getMove()->isCapture)
            {
                $capturedPiece = $this->getPieceByPosition($piece->getMove()->position->next);
                $this->detach($capturedPiece);
            }
            // if the piece is a pawn, try to promote
            if ($piece->getIdentity() === PGN::PIECE_PAWN  && $piece->isPromoted())
            {
                $this->detach($piece);
                switch($piece->getMove()->newIdentity)
                {
                    case PGN::PIECE_KNIGHT:
                        $this->attach(new Knight($piece->getColor(), $piece->getMove()->position->next));
                        break;

                    case PGN::PIECE_BISHOP:
                        $this->attach(new Bishop($piece->getColor(), $piece->getMove()->position->next));
                        break;

                    case PGN::PIECE_ROOK:
                        $this->attach(new Rook($piece->getColor(), $piece->getMove()->position->next));
                        break;

                    default:
                        $this->attach(new Queen($piece->getColor(), $piece->getMove()->position->next));
                        break;
                }
            }
            // update status
            $this->updateStatus($piece);
        }
        catch (\Exception $e)
        {
            // TODO log exception...
            return false;
        }
        return true;
    }

    /**
     * Gets all pieces by color.
     *
     * @param string $color
     *
     * @return array
     */
    public function getPiecesByColor($color)
    {
        $pieces = [];
        $this->rewind();
        while ($this->valid())
        {
            $piece = $this->current();
            $piece->getColor() === $color ? $pieces[] = $piece : false;
            $this->next();
        }
        return $pieces;
    }

    /**
     * Gets the first piece on the board meeting the searching criteria.
     *
     * @param string $color
     * @param string $identity
     * @return PGNChess\Piece
     */
    public function getPiece($color, $identity)
    {
        $this->rewind();
        while ($this->valid())
        {
            $piece = $this->current();
            if ($piece->getColor() === $color && $piece->getIdentity() === $identity)
            {
                return $piece;
            }
            $this->next();
        }
        return null;
    }

    /**
     * Gets a piece by its position on the board.
     *
     * @param string $square
     *
     * @return PGNChess\Piece
     */
    public function getPieceByPosition($square)
    {
        $this->rewind();
        while ($this->valid())
        {
            $piece = $this->current();
            if ($piece->getPosition()->current === $square)
            {
                return $piece;
            }
            $this->next();
        }
        return null;
    }

   /**
    * Builds an object containing the squares currently being controlled by both players.
    * This corresponds with the idea of space in chess. And more specifically, it is
    * helpful to decide whether or not a king can be put on this or that square of the board.
    *
    * @see Board::KingIsMoved(King $king)
    *
    * @return stdClass
    */
    private function space()
    {
        $space = (object) [
            PGN::COLOR_WHITE => [],
            PGN::COLOR_BLACK => []
        ];
        $this->rewind();
        while ($this->valid())
        {
            $piece = $this->current();
            switch($piece->getIdentity())
            {
                case PGN::PIECE_KING:
                    $space->{$piece->getColor()} = array_unique(
                        array_merge(
                            $space->{$piece->getColor()},
                            array_values(
                                array_intersect(
                                    array_values((array)$piece->getPosition()->scope),
                                    $this->status->squares->free
                                )
                            )
                        )
                    );
                    break;

                case PGN::PIECE_PAWN:
                    $space->{$piece->getColor()} = array_unique(
                        array_merge(
                            $space->{$piece->getColor()},
                            array_intersect(
                                $piece->getPosition()->capture,
                                $this->status->squares->free
                            )
                        )
                    );
                    break;

                default:
                    $space->{$piece->getColor()} = array_unique(
                        array_merge(
                            $space->{$piece->getColor()},
                            array_diff(
                                $piece->getLegalMoves(),
                                $this->status->squares->used->{$piece->getOppositeColor()}
                            )
                        )
                    );
                    break;
            }
            $this->next();
        }
        sort($space->{PGN::COLOR_WHITE});
        sort($space->{PGN::COLOR_BLACK});
        return $space;
    }

    /**
     * Builds an object containing the squares currently being attacked by both players.
     *
     * @return stdClass
     */
    private function attack()
    {
        $attack = (object) [
            PGN::COLOR_WHITE => [],
            PGN::COLOR_BLACK => []
        ];
        $this->rewind();
        while ($this->valid())
        {
            $piece = $this->current();
            switch($piece->getIdentity())
            {
                case PGN::PIECE_KING:
                    $attack->{$piece->getColor()} = array_unique(
                        array_merge(
                            $attack->{$piece->getColor()},
                            array_values(
                                array_intersect(
                                    array_values((array)$piece->getPosition()->scope),
                                    $this->status->squares->used->{$piece->getOppositeColor()}
                                )
                            )
                        )
                    );
                    break;

                case PGN::PIECE_PAWN:
                    $attack->{$piece->getColor()} = array_unique(
                        array_merge(
                            $attack->{$piece->getColor()},
                            array_intersect(
                                $piece->getPosition()->capture,
                                $this->status->squares->used->{$piece->getOppositeColor()}
                            )
                        )
                    );
                    break;

                default:
                    $attack->{$piece->getColor()} = array_unique(
                        array_merge(
                            $attack->{$piece->getColor()},
                            array_intersect(
                                $piece->getLegalMoves(),
                                $this->status->squares->used->{$piece->getOppositeColor()}
                            )
                        )
                    );
                    break;
            }
            $this->next();
        }
        sort($attack->{PGN::COLOR_WHITE});
        sort($attack->{PGN::COLOR_BLACK});
        return $attack;
    }

    /**
     * Verifies whether or not a piece's move leaves the board in check. This is
     * like going to the future to see what will happen in the next move in order
     * to take a decision accordingly. It "forks" the current board and simulates
     * a piece move on it. Here is the idea actually being implemented: (1) a piece
     * is moved on the board (2) if it turns out that the king is attacked, then
     * we say the board is in check.
     *
     * @param PGNChess\Piece $piece
     *
     * @return boolean
     */
    private function isCheck($piece)
    {
        $that = clone $this;
        $that->pieceIsMoved($piece);
        $king = $that->getPiece($piece->getColor(), PGN::PIECE_KING);
        if (in_array($king->getPosition()->current, $that->attack()->{$king->getOppositeColor()}))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
