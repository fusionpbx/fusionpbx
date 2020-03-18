import sys, argparse

parser = argparse.ArgumentParser(description="""Copies fusionpbx webapp languages. Run with 'find <fusionpbx_dir> -name app_config.php -exec python ./cp_lang.py {} en-gb en-us \;'""")
parser.add_argument("php", help="app_config.php, app_languages.php or app_menu.php file path", type=str)
parser.add_argument("new", help="new language code", type=str)
parser.add_argument("cur", help="current language code to copy", type=str)
args = parser.parse_args()

with open(args.php, "r+") as f:
	contents = f.readlines()
	for index, line in enumerate(contents):
		find = "['{}']".format(args.cur)
		repl = "['{}']".format(args.new)
		if find in line and "['fields']" not in line:
			newline = line.replace(find, repl)
			if newline not in contents:
				contents.insert(index+1, newline)
	f.seek(0)
	f.writelines(contents)
