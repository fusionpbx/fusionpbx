#!version:1.0.0.1
## The header above must appear as-is in the first line

[T19P_E2]include:config "y000000000053.cfg"
[T21P_E2]include:config "y000000000052.cfg"
[T23P]include:config "y000000000044.cfg"
[T23G]include:config "y000000000044.cfg"
[T27G]include:config "y000000000069.cfg"
[T29G]include:config "y000000000046.cfg"
include:config "{$mac}.cfg"

overwrite_mode = {$yealink_overwrite_mode}
