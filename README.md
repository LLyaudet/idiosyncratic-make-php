# idiosyncratic-make-php
Why learn another micro-language when PHP has already much more features?

Example:
- Standard Makefile
```Makefile
CC=gcc
CFLAGS=-Wall -WExtra
CFLAGS2=-Wall -g
OBJECTS_DIRECTORY=build/
OBJECTS=$(OBJECTS_DIRECTORY)my_C_file.o $(OBJECTS_DIRECTORY)my_C_file2.o

bin/my_program.exe: $(OBJECTS)
  $(CC) $(CFLAGS) -o $@ $(OBJECTS)

$(OBJECTS_DIRECTORY)my_C_file.o: my_C_file.c
  $(CC) $(CFLAGS) -c my_C_file.c -o $@

$(OBJECTS_DIRECTORY)my_C_file2.o: my_C_file2.c
  $(CC) $(CFLAGS2) -c my_C_file.c -o $@

echo_something:
  @echo "Flags 1: $(CFLAGS)"
  @echo "Flags 2: $(CFLAGS2)"
```

- IdiosyncraticMakeFile (PHP version)
```php
#!/usr/bin/php
<?php
use IdiosyncraticMake;

$CC = "gcc";
$CFLAGS = ["-Wall", "-WExtra"];
$CFLAGS2 = "-Wall -g";
$OBJECTS_DIRECTORY = "build/";
$OBJECTS = [
  $OBJECTS_DIRECTORY."my_C_file.o",
  $OBJECTS_DIRECTORY."my_C_file2.o",
];

make_target(
  "bin/my_program.exe",
  $OBJECTS,
  [$CC." ".implode(" ", $CFLAGS)." -o $@ ".implode(" ", $OBJECTS)],
  default_goal: true,
)

make_target(
  $OBJECTS_DIRECTORY."my_C_file.o",
  ["my_C_file.c"],
  [$CC." ".implode(" ", $CFLAGS)." -c my_C_file.c -o $@"],
)

make_target(
  $OBJECTS_DIRECTORY."my_C_file2.o",
  ["my_C_file.c"],
  [$CC." ".$CFLAGS2." -c my_C_file2.c -o $@"],
)

make_target(
  "echo_something",
  [],
  [
    "echo \"Flags 1: ".implode(" ", $CFLAGS)."\"",
    "echo \"Flags 2: ".$CFLAGS2."\"",
  ],
)

make($argc, $argv);
```
