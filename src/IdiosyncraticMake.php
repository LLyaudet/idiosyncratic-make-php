<?php

/**
This file is part of IdiosyncraticMake.

IdiosyncraticMake is free software:
you can redistribute it and/or modify it
under the terms of the GNU Lesser General Public License
as published by the Free Software Foundation,
either version 3 of the License,
or (at your option) any later version.

IdiosyncraticMake is distributed
in the hope that it will be useful,
but WITHOUT ANY WARRANTY;
without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU Lesser General Public License for more details.

You should have received a copy of
the GNU Lesser General Public License
along with IdiosyncraticMake.
If not, see <https://www.gnu.org/licenses/>.

©Copyright 2023-2026 Laurent Frédéric Bernard François Lyaudet
This file was renamed from "unicode.php" to "unicode.libr.php".

@category Library
@package IdiosyncraticMake
@author Laurent Lyaudet <laurent.lyaudet@gmail.com>
@copyright 2026 Laurent Frédéric Bernard François Lyaudet
@license https://www.gnu.org/licenses/lgpl-3.0.html LGPLv3+
*/

declare(strict_types=1);

namespace IdiosyncraticMake;




class Configuration{
  // À la GNU Make, merge prerequisites if empty recipes
  const I_MULTIPLE_RULES_FOR_ONE_TARGET__ONLY_ALLOW_IMPLICIT_RULES = 1;
  // Merge prerequisites if recipe agree
  const I_MULTIPLE_RULES_FOR_ONE_TARGET__ONLY_ALLOW_IF_SAME_RECIPE = 2;
  // Throw an Exception
  const I_MULTIPLE_RULES_FOR_ONE_TARGET__NEVER_ALLOW = 3;
  // Merge prerequisites and concatenate recipes
  const I_MULTIPLE_RULES_FOR_ONE_TARGET__ALWAYS_ALLOW = 4;

  /**
  The variable that allows to define rules
  that are identic for distinct targets.
  Assume that two files are the by-product of the same recipe,
  and that you may need one of these files without needing the other.
  Or that you want to have a few aliases to some phony rules.
  Then set this variable to true,
  and IdiosyncraticMake will not throw an Exception when you define
  rules with multiple targets outside of cases allowed by GNU make.

  @var bool
  */
  public static $b_allow_one_recipe_multiple_targets = false;

  /**
  The variable that allows to define multiple rules for the same target.
  @var int
  */
  public static $i_multiple_rules_for_one_target = (
    self::I_MULTIPLE_RULES_FOR_ONE_TARGET__ONLY_ALLOW_IMPLICIT_RULES
  );
}




class IdiosyncraticMake{
  // @TODO weight the pros and cons of static vs object.
  // But I prefer a class for the configuration above.
  // And I prefer static because otherwise the key functions calls,
  // like create_makefile_rule() in the Makefile/PHP Frankenstein's script
  // will be more verbose. WIP.

  /**
  The variable that will contain the rules of your
  Makefile script.

  @var array
  */
  public static $arr_rules = [];



  /**
  This "technical" function adds a rule to arr_rules dealing with some
  deduplication techniques.

  @param string $s_target The target name.
  @param array $arr_rule The rule to add.

  @return void
  */
  public function add_rule_to_target($s_target, $arr_rule){
    if(isset(self::$arr_rules[$s_target])){
      if(
        Configuration::$i_multiple_rules_for_one_target
        === Configuration::I_MULTIPLE_RULES_FOR_ONE_TARGET__NEVER_ALLOW
      ){
        throw new \Exception(
          "Only one rule is allowed for the same target ".$s_target
          ." Set IdiosyncraticMake\Configuration::"
          ."\$i_multiple_rules_for_one_target to some other value"
          ." if needed."
        );
      }
      $rule = self::$arr_rules[$s_target];
      $i_max = count($rule["recipe"]);
      if(count($arr_rule["recipe"]) !== $i_max){
        if(
          Configuration::$i_multiple_rules_for_one_target
          !== Configuration::I_MULTIPLE_RULES_FOR_ONE_TARGET__ALWAYS_ALLOW
        ){
          throw new \Exception(
            "Only multiple rules with same recipe (empty or not)"
            ." are allowed for the same target ".$s_target
            ." Set IdiosyncraticMake\Configuration::"
            ."\$i_multiple_rules_for_one_target to some other value"
            ." if needed."
          );
        }
      }
      else{
        for($i = 0; $i < $i_max; ++$i){
          if($arr_rule["recipe"][$i] !== $rule["recipe"][$i]){
            break;
          }
        }
      }
      if(
        $i_max > 0
        && Configuration::$i_multiple_rules_for_one_target
        ===
  Configuration::I_MULTIPLE_RULES_FOR_ONE_TARGET__ONLY_ALLOW_IMPLICIT_RULES
      ){
        throw new \Exception(
          "Only multiple implicit rules with empty recipe"
          ." are allowed for the same target ".$s_target
          ." Set IdiosyncraticMake\Configuration::"
          ."\$i_multiple_rules_for_one_target to some other value"
          ." if needed."
        );
      }

      if(
        Configuration::$i_multiple_rules_for_one_target
        === Configuration::I_MULTIPLE_RULES_FOR_ONE_TARGET__ALWAYS_ALLOW
      ){
        // We concatenate the recipes.
        $rule["prerequisites"] += $arr_rule["prerequisites"];
      }
      elseif($i !== $i_max){  // Always false when empty (i_max === 0)
        throw new \Exception(
          "Only multiple rules with same recipe"
          ." are allowed for the same target ".$s_target
          ." Set IdiosyncraticMake\Configuration::"
          ."\$i_multiple_rules_for_one_target to some other value"
          ." if needed."
        );
      }
      // We merge the prerequisites.
      $arr_prerequisites = array_merge(
        $rule["prerequisites"],
        $arr_rule["prerequisites"],
      );
      $rule["prerequisites"] = $arr_prerequisites;
    }
    self::$arr_rules[$s_target] = $arr_rule;
  }
}




/**
This function fills the variable IdiosyncraticMakefile::$arr_rules with
a usage syntax that is PHP but can be made close to what you would write
in a standard Makefile.
Note that implicit rules allow to create multiple rules with a single call
to this function.

@param string|array $target The target name to create.
@param array $arr_s_prerequisites The prerequisites to build before.
@param array $arr_s_commands The commands to execute to build the target.

@throws \Exception If rule is not valid.

@return void
*/
function create_makefile_rule(
  string|array $target,
  array $arr_s_prerequisites,
  array $arr_s_commands,
  bool $b_default_goal = false,
){
  if(!is_array($target)){
    $target = [$target];
  }

  if(
    count($arr_s_commands) > 0
    && count($target) > 1
    && !Configuration::$b_allow_one_recipe_multiple_targets
  ){
    throw new \Exception(
      "Rule with targets ".implode(" ", $target)." is not an implicit rule"
      .", and hence is not allowed when compatible with GNU make."
      ." Set IdiosyncraticMake\Configuration::"
      ."\$b_allow_one_recipe_multiple_targets = true;"
      ." if you do need this feature."
    );
  }

  foreach($target as $s_target){
    // Implicit rules
    // @TODO multiple implicit rules may apply at execution.
    // $arr_s_commands should remain empty, and be handled by
    // implicit rules mechanism after that.
    // Code below should just be replaced by some check that at least one
    // implicit rule could apply.
    // The rule for default build of .o from .c.
    if(count($arr_s_commands) === 0){
      if(str_ends_with($s_target, ".o")){
        $s_usual_prerequisite = substr($s_target, 0, -2).".c";
        if(!in_array($s_usual_prerequisite, $arr_s_prerequisites, true)){
          $arr_s_prerequisites []= $s_usual_prerequisite;
        }
        $arr_s_commands = "cc -c ".$s_usual_prerequisite." -o ".$s_target;
      }
    }

    add_rule_to_target(
      $s_target,
      [
        "target" => $s_target,
        "prerequisites" => $arr_s_prerequisites,
        "recipe" => $arr_s_commands,
        "default_goal" => $b_default_goal,
      ]
    );
  }
}



/**
This function builds one of the registered targets.

@param string $s_target The target to build.

@return void
*/
function build_target(string $s_target, bool $b_verbose = false){
  if($b_verbose){
    echo "Building $s_target...\n";
  }
  foreach(
    IdiosyncraticMakefile::$arr_rules[$s_target]["recipe"] as $s_command
  ){
    $s_command = str_replace(
      "$@",
      escapeshellarg($s_target),
      $s_command,
    );
    system($s_command) === false && die("Build failed for $s_target");
  }
}



/**
This function checks if a target needs rebuild.

@param string $s_target The target to check for rebuild.

@return void
*/
function target_needs_rebuild(string $s_target){
  $b_needs_rebuild = !file_exists($s_target);
  if(!$b_needs_rebuild){
    foreach(
      IdiosyncraticMakefile::$arr_rules[$s_target]["prerequisites"]
      as $s_prerequisite
    ){
      if(
        !file_exists($s_prerequisite)
        || filemtime($s_prerequisite) > filemtime($s_target)
      ){
        $b_needs_rebuild = true;
        break;
      }
    }
  }
}



/**
This function builds one of the registered targets,
but builds its prerequisites first if needed.

@param string $s_target The target to build.

@return void
*/
function build_target_with_prerequisites(
  string $s_target,
  bool $b_verbose = false,
){
  foreach(
    IdiosyncraticMakefile::$arr_rules[$s_target]["prerequisites"]
    as $s_prerequisite
  ){
    if(target_needs_rebuild($s_prerequisite)){
      build_target_with_prerequisites($s_prerequisite, $b_verbose);
    }
  }
  if(target_needs_rebuild($s_target)){
    build_target($s_target, $b_verbose);
  }
}



/**
This function makes the script execute similarly to a call to GNU Make
with a corresponding Makefile.

@TODO This is work in progress, but you get the idea.
The goal being to support a set of features as rich as GNU Make,
plus the possibility that PHP adds on top of it.

@param int $argc The number of command line arguments.
@param array $argv The command line arguments.

@return void
*/
function make($argc, $argv){
  foreach(IdiosyncraticMakefile::$arr_rules as $s_target => $arr_rule){
    if($arr_rule["default_goal"]){
      build_target_with_prerequisites($s_target);
    }
  }
}
?>
