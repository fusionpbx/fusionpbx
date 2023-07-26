function basename(file_name)
	return (string.match(file_name, "([^/]+)$"))
end

return basename
