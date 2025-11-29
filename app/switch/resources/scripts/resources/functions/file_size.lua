
--get the file size
function file_size(file_path)
    -- Open the file for reading
    local file = io.open(file_path, "r");

    --return 0 if unable to open the file
    if not file then
        return 0;
    end

    -- Seek to the end of the file and get the position
    local size = file:seek("end");

    -- Close the file
    file:close();

    return size;
end
