#!version:1.0.0.1
## The header above must appear as-is in the first line


##[$MODEL]include:config <xxx.cfg>
##[$MODEL,$MODEL]include:config "xxx.cfg"  
  
include:config "y000000000150.cfg"
include:config "{$mac}.cfg"

overwrite_mode = {$yealink_overwrite_mode}
