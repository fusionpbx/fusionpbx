import os
import re
import chardet

def add_georgian_strings(file_path):
    with open(file_path, 'rb') as file:
        raw_data = file.read()
    detected = chardet.detect(raw_data)
    encoding = detected['encoding']

    try:
        with open(file_path, 'r', encoding=encoding) as file:
            content = file.readlines()
    except UnicodeDecodeError:
        print(f"Unable to decode file: {file_path} with detected encoding: {encoding}")
        return

    pattern = r"(\$(?:apps\[\$x\]|text)\['([^']+)'\]\['([^']+)'\]\s*=\s*)(\"[^\"]*\"|'[^']*');$"
    changes_made = False

    new_content = []
    for line in content:
        new_content.append(line)
        match = re.match(pattern, line.strip())
        if match and match.group(3) == 'it-it':
            indent = re.match(r"(\s*)", line).group(1)
            prefix, key, _, value = match.groups()
            georgian_line = f"{indent}{prefix.replace('it-it', 'ka-ge')}\"\";\n"
            new_content.append(georgian_line)
            changes_made = True

    if changes_made:
        try:
            with open(file_path, 'w', encoding=encoding) as file:
                file.writelines(new_content)
            print(f"Updated: {file_path}")
        except UnicodeEncodeError:
            print(f"Unable to write to file: {file_path} with encoding: {encoding}")
    else:
        print(f"No changes needed: {file_path}")

def process_directory(directory):
    for root, dirs, files in os.walk(directory):
        for file in files:
            if file.endswith('.php'):
                file_path = os.path.join(root, file)
                print(f"Processing: {file_path}")
                add_georgian_strings(file_path)

if __name__ == "__main__":
    directory_path = input("Enter the directory path to search for PHP files: ")
    process_directory(directory_path)
    print("Processing complete.")

