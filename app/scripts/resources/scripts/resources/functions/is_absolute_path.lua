function is_absolute_path(file_name)
	return string.sub(file_name, 1, 1) == '/' or string.sub(file_name, 2, 1) == ':'
end

return is_absolute_path
