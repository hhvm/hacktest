<?hh // strict
/*
 *  Copyright (c) 2018-present, Facebook, Inc.
 *  All rights reserved.
 *
 *  This source code is licensed under the MIT license found in the
 *  LICENSE file in the root directory of this source tree.
 *
 */

namespace Facebook\HackTest;

use namespace HH\Lib\{C, Math, Str, Vec};

/** Class to represent and parse a docblock (a.k.a. doc comment).
 *
 * These comments always are delimeted by `/**` and `*\/`.
 *
 * DocBlocks are treated as GitHub Flavored Markdown, and standard
 * JavaDoc style tags are supported, such as `@param`, `@see`, and
 * `@return`.
 */
final class DocBlock {
  private ?string $summary;
  private ?string $description;

  private vec<(string, ?string)> $tags = vec[];

  /** Create and parse a documentation block.
   *
   * @param $rawDocBlock A raw documentation block, as returned by reflection or
   *   definition-finder.
   */
  public function __construct(private string $rawDocBlock) {
    $lines = $rawDocBlock
      |> Str\trim($$)
      |> Str\strip_prefix($$, '/**')
      |> Str\strip_suffix($$, '*/')
      |> Str\trim($$)
      |> Str\split($$, "\n")
      |> Vec\map($$, $l ==> Str\trim_left($l));

    $content_lines = vec[];
    $finished_content = false;
    $at_lines = vec[];

    foreach ($lines as $line) {
      if (Str\starts_with($line, '* ')) {
        $line = Str\strip_prefix($line, '* ');
      } else {
        $line = Str\strip_prefix($line, '*');
      }

      if (Str\starts_with($line, '@')) {
        $finished_content = true;
      }

      if ($finished_content) {
        $at_lines[] = $line;
        continue;
      } else {
        $content_lines[] = $line;
        continue;
      }
    }

    $content = Str\trim_left(Str\join($content_lines, "\n"));

    $first_period = Str\search($content, '.');
    if ($first_period !== null) {
      // Handle '...'
      $slice = Str\slice($content, $first_period);
      $x = Str\trim_left($slice, '.');
      $diff = Str\length($slice) - Str\length($x);
      if ($diff > 2) {
        $first_period = Str\search($content, '.', $first_period + $diff);
      }
    }

    $first_para = Str\search($content, "\n\n");
    if ($first_period === null && $first_para !== null) {
      $sep = $first_para;
    } else if ($first_period !== null && $first_para === null) {
      $sep = $first_period;
    } else if ($first_period !== null && $first_para !== null) {
      $sep = Math\minva($first_para, $first_period);
    } else {
      $sep = null;
    }

    if ($sep === null && $content !== '') {
      $this->summary = $content;
    } else if ($sep !== null) {
      $this->summary = Str\trim(Str\slice($content, 0, $sep));
      $description = Str\trim(Str\slice($content, $sep + 1));
      if ($description !== '') {
        $this->description = $description;
      }
    }

    $current_tag = null;
    $tags = vec[];

    foreach ($at_lines as $line) {
      $line = Str\trim($line);
      if (Str\starts_with($line, '@')) {
        if ($current_tag !== null) {
          $tags[] = $current_tag;
        }
        $current_tag = $line;
      } else {
        $current_tag .= "\n".$line;
      }
    }
    if ($current_tag !== null) {
      $tags[] = $current_tag;
    }

    foreach ($tags as $tag) {
      $space = Str\search($tag, ' ');
      if ($space === null) {
        $this->tags[] = tuple($tag, null);
        continue;
      }
      $key = Str\slice($tag, 0, $space);
      $value = Str\trim(Str\slice($tag, $space + 1));
      $this->tags[] = tuple($key, $value);
    }
  }

  /** Get the summary of the item being documented.
   *
   * This is the content of the doccomment until the first empty line or period.
   *
   * In the future, this may parse the doccomment as markdown, and return
   * the content until the first empty line _or plain text_ period.
   */
  public function getSummary(): ?string {
    return $this->summary;
  }

  /** Get the description of the item being documented.
   *
   * This is anything that is not an `@tag` or summary.
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /** Return the content of all tags with the specified name.
   *
   * For example, `getTagsByName('@param')` will return all
   * `@param` tags.
   */
  public function getTagsByName(string $name): vec<string> {
    return $this->tags
      |> Vec\filter(
        $$,
        $tag ==> {
          list($key, $value) = $tag;
          if ($key !== $name) {
            return false;
          }
          if ($value === null) {
            return false;
          }
          return true;
        },
      )
      |> Vec\map($$, $x ==> $x[1])
      |> Vec\filter_nulls($$);
  }

  /** Get the return information for a function.
   *
   * There may be multiple `ReturnInfo` results, if there are multiple
   * `@return` tags in the docblock.
   */
  public function getReturnInfo(): vec<ReturnInfo> {
    $out = vec[];
    foreach ($this->tags as list($key, $value)) {
      if ($key !== '@return' && $key !== '@returns') {
        continue;
      }
      if ($value === null) {
        continue;
      }

      $space = Str\search($value, ' ');
      if ($space === null) {
        $out[] = shape('type' => $value, 'text' => null);
        continue;
      }

      $text = Str\trim(Str\slice($value, $space));
      if ($text === '') {
        $text = null;
      }
      $out[] = shape(
        'type' => Str\slice($value, 0, $space),
        'text' => $text,
      );
    }
    return Vec\map(
      $out,
      $x ==> shape(
        'text' => $x['text'],
        'types' => self::typeToTypes($x['type']),
      ),
    );
  }

  /** Convert a string type specifiction to a list of types.
   *
   * For example:
   * - `'string'` -> `vec['string']`
   * - `'Foo|Bar'` -> `vec['Foo', 'Bar']`
   * - `'[Foo|Bar]'` -> `vec['Foo', 'Bar']`
   */
  protected static function typeToTypes(?string $type): vec<string> {
    if ($type === null) {
      return vec[];
    }
    return $type
      |> Str\strip_prefix($$, '[')
      |> Str\strip_suffix($$, ']')
      |> Str\split($$, '|')
      |> Vec\map($$, $x ==> Str\trim($x));
  }

  /** Return information on function parameters from `@param` tags */
  <<__Memoize>>
  public function getParameterInfo(): dict<string, ParameterInfo> {
    $out = dict[];
    foreach ($this->tags as list($key, $value)) {
      if ($key !== '@param') {
        continue;
      }
      if ($value === null) {
        continue;
      }
      $name = null;

      $dollar = Str\search($value, '$');
      if ($dollar === null) {
        continue;
      }

      $space = Str\search($value, ' ');

      if ($space === null) {
        $type = null;
        $space = Str\length($value);
      } else if ($space > $dollar) {
        $type = null;
      } else {
        $type = Str\trim(Str\slice($value, 0, $dollar - 1));
        if ($type === '') {
          $type = null;
        }
      }

      $space = Str\search($value, ' ', $dollar);
      if ($space === null) {
        $name = Str\slice($value, $dollar);
        $text = null;
      } else {
        $name = Str\slice($value, $dollar, $space - $dollar);
        $text = Str\trim(Str\slice($value, $space));
      }
      $out[$name] = shape(
        'name' => $name,
        'types' => self::typeToTypes($type),
        'text' => $text,
      );
    }
    return $out;
  }

  /** Create a new instance if a comment is provided.
   *
   * @returns `DocComment` if `$comment` is not null
   * @returns `null` if `$comment` is null
   */
  public static function nullable(?string $comment): ?this {
    if ($comment === null) {
      return null;
    }
    if ($comment === '') {
      return null;
    }
    return new self($comment);
  }
}
