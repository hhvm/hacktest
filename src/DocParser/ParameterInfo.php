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
/** The documentation information for a parameter. */
type ParameterInfo = shape(
  /** The name of the parameter */
  'name' => string,
  /** The valid types for the parameter */
  'types' => vec<string>,
  /** The human-readable text for the parameter.
   *
   * Likely to be GitHub-Flavored-Markdown.
   */
  'text' => ?string,
);
