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
/** The documented information on what a function or method returns. */
type ReturnInfo = shape(
  /** The types that the function or method can return. */
  'types' => vec<string>,
  /** Free-form text.
   *
   * Likely to be GitHub-Flavored-Markdown.
   */
  'text' => ?string,
);
