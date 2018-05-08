%include {/*
    This file is part of Erebot, a modular IRC bot written in PHP.

    Copyright © 2010 François Poirotte

    Erebot is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Erebot is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Erebot.  If not, see <http://www.gnu.org/licenses/>.
*/
}
%declare_class {class PluralParser}
%syntax_error { throw new \Exception("Invalid plural expression ($yymajor, $TOKEN)"); }
%token_prefix TK_
%include {
    // @codingStandardsIgnoreFile
    namespace Erebot\Intl;
}

%include_class {
    protected $evaluator;

    public function __construct($expr)
    {
        $this->evaluator = null;
        $paren      = 0;
        $operators  = array(
            '%'     => self::TK_OP_MOD,
            '?'     => self::TK_OP_QUESTION,
            ':'     => self::TK_OP_COLON,
            '>'     => self::TK_OP_GT,
            '<'     => self::TK_OP_LT,
            '-'     => self::TK_OP_SUB,
            '+'     => self::TK_OP_ADD,
            '*'     => self::TK_OP_TIMES,
            '/'     => self::TK_OP_DIVIDE,
            '!'     => self::TK_OP_NOT,
            '>='    => self::TK_OP_GTE,
            '<='    => self::TK_OP_LTE,
            '=='    => self::TK_OP_EQ,
            '!='    => self::TK_OP_NE,
            '&&'    => self::TK_OP_LOG_AND,
            '||'    => self::TK_OP_LOG_OR,
            '~'     => self::TK_OP_BIT_NEG,
            '&'     => self::TK_OP_BIT_AND,
            '|'     => self::TK_OP_BIT_OR,
            '<<'    => self::TK_OP_BIT_SHL,
            '>>'    => self::TK_OP_BIT_SHR,
            '('     => self::TK_PAREN_OPEN,
            ')'     => self::TK_PAREN_CLOSE,
        );
        $ws     = array(" ", "\t");

        for ($i = 0, $len = strlen($expr); $i < $len; $i++) {
            // Whitespace
            if (in_array($expr[$i], $ws)) {
                continue;
            }

            // Binary operators
            $op = substr($expr, $i, 2);
            if (isset($operators[$op])) {
                $this->doParse($operators[$op], $op);
                $i++; // The loop will increment it by +1 too.
                continue;
            }

            // Unary operators and special symbols
            // (eg. ternary operator & parentheses)
            if (isset($operators[$expr[$i]])) {
                $this->doParse($operators[$expr[$i]], $expr[$i]);
                continue;
            }

            // Hexadecimal number
            if ($op === '0x' || $op === '0X') {
                $nlen = strspn($expr, "1234567890abcdefABCDEF", $i + 2);
                if (!$nlen) {
                    throw new \InvalidArgumentException('Invalid plural expression');
                }
                $this->doParse(self::TK_NUMBER, substr($expr, $i, $nlen + 2));
                $i += $nlen + 1; // The loop will increment it by +1 too.
                continue;
            }

            // Octal number
            if ($expr[$i] === '0') {
                $nlen = strspn($expr, "01234567", $i);
                $this->doParse(self::TK_NUMBER, substr($expr, $i, $nlen));
                $i += $nlen - 1; // The loop will increment it by +1.
                continue;
            }

            // Decimal number
            $nlen = strspn($expr, "1234567890", $i);
            if ($nlen > 0) {
                $this->doParse(self::TK_NUMBER, substr($expr, $i, $nlen));
                $i += $nlen - 1; // The loop will increment it by +1.
                continue;
            }

            // Assume everything else refers to a symbol
            $this->doParse(self::TK_VARIABLE, $expr[$i]);
        }

        // End of tokenization.
        $this->doParse(0, 0);

        if ($this->evaluator === null) {
            throw new \InvalidArgumentException('Invalid plural expression');
        }
    }

    public function getEvaluator()
    {
        return $this->evaluator;
    }
}

%right      OP_QUESTION OP_COLON.
%left       OP_LOG_OR.
%left       OP_LOG_AND.
%left       OP_BIT_OR.
%left       OP_BIT_XOR.
%left       OP_BIT_AND.
%nonassoc   OP_EQ OP_NE.
%nonassoc   OP_GT OP_LT OP_GTE OP_LTE.
%left       OP_BIT_SHL OP_BIT_SHR.
%left       OP_ADD OP_SUB.
%left       OP_TIMES OP_DIVIDE OP_MOD.
%right      OP_NOT OP_BIT_NEG.

result ::= expr(e). { $this->evaluator = eval('return function ($n) { return (int) (' . e . '); };'); }

expr(res) ::= expr(c) OP_QUESTION expr(t) OP_COLON expr(f). { res = "(" . c . ") ? (" . t . ") : (" . f . ")"; }
expr(res) ::= expr(e1) OP_LOG_AND   expr(e2).               { res = e1 . " && " . e2; }
expr(res) ::= expr(e1) OP_LOG_OR    expr(e2).               { res = e1 . " || " . e2; }
expr(res) ::= expr(e1) OP_GT        expr(e2).               { res = e1 . " > "  . e2; }
expr(res) ::= expr(e1) OP_LT        expr(e2).               { res = e1 . " < "  . e2; }
expr(res) ::= expr(e1) OP_EQ        expr(e2).               { res = e1 . " == " . e2; }
expr(res) ::= expr(e1) OP_NE        expr(e2).               { res = e1 . " != " . e2; }
expr(res) ::= expr(e1) OP_GTE       expr(e2).               { res = e1 . " >= " . e2; }
expr(res) ::= expr(e1) OP_LTE       expr(e2).               { res = e1 . " <= " . e2; }
expr(res) ::= expr(e1) OP_MOD       expr(e2).               { res = e1 . " % "  . e2; }
expr(res) ::= expr(e1) OP_ADD       expr(e2).               { res = e1 . " + "  . e2; }
expr(res) ::= expr(e1) OP_SUB       expr(e2).               { res = e1 . " - "  . e2; }
expr(res) ::= expr(e1) OP_TIMES     expr(e2).               { res = e1 . " * "  . e2; }
expr(res) ::= expr(e1) OP_DIVIDE    expr(e2).               { res = e1 . " / "  . e2; }
expr(res) ::= expr(e1) OP_BIT_AND   expr(e2).               { res = e1 . " & "  . e2; }
expr(res) ::= expr(e1) OP_BIT_OR    expr(e2).               { res = e1 . " | "  . e2; }
expr(res) ::= expr(e1) OP_BIT_XOR   expr(e2).               { res = e1 . " ^ "  . e2; }
expr(res) ::= expr(e1) OP_BIT_SHL   expr(e2).               { res = e1 . " << " . e2; }
expr(res) ::= expr(e1) OP_BIT_SHR   expr(e2).               { res = e1 . " >> " . e2; }
expr(res) ::= OP_SUB expr(e). [OP_NOT]                      { res = "-" . e; }
expr(res) ::= OP_AND expr(e). [OP_NOT]                      { res = "+" . e; }
expr(res) ::= OP_BIT_NEG    expr(e).                        { res = "~" . e; }
expr(res) ::= OP_NOT        expr(e).                        { res = "!" . e; }
expr(res) ::= PAREN_OPEN expr(e) PAREN_CLOSE.               { res = "(" . e . ")"; }
expr(res) ::= VARIABLE(v).                                  {
    if (v !== 'n') {
        throw new \Exception('Invalid plural expression');
    }
    res = '$n';
}
expr(res) ::= NUMBER(n).                                    { res = n; }

