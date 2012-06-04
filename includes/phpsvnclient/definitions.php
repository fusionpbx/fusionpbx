<?php
/*
***************************************************************************
*   Copyright (C) 2007-2008 by Sixdegrees                                 *
*   cesar@sixdegrees.com.br                                               *
*   "Working with freedom"                                                *
*   http://www.sixdegrees.com.br                                          *
*                                                                         *
*   Modified by Ethan Smith (ethan@3thirty.net), April 2008               *
*      - Added definitions to fetch modified files with commit logs and   *
*        for reading these filename                                       *
*                                                                         *
*   Permission is hereby granted, free of charge, to any person obtaining *
*   a copy of this software and associated documentation files (the       *
*   "Software"), to deal in the Software without restriction, including   *
*   without limitation the rights to use, copy, modify, merge, publish,   *
*   distribute, sublicense, and/or sell copies of the Software, and to    *
*   permit persons to whom the Software is furnished to do so, subject to *
*   the following conditions:                                             *
*                                                                         *
*   The above copyright notice and this permission notice shall be        *
*   included in all copies or substantial portions of the Software.       *
*                                                                         *
*   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,       *
*   EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF    *
*   MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.*
*   IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR     *
*   OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, *
*   ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR *
*   OTHER DEALINGS IN THE SOFTWARE.                                       *
***************************************************************************
*/
define("PHPSVN_NORMAL_REQUEST2",
'<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
	<allprop/>
</propfind>');

define("PHPSVN_NORMAL_REQUEST",
'<?xml version="1.0" encoding="utf-8"?>
<propfind xmlns="DAV:">
<prop>
<getlastmodified xmlns="DAV:"/>
<baseline-relative-path xmlns="http://subversion.tigris.org/xmlns/dav/"/>
<md5-checksum xmlns="http://subversion.tigris.org/xmlns/dav/"/>
</prop>
</propfind>');

//<version-name xmlns="DAV:"/>
//<getlastmodified xmlns="DAV:"/>

define("PHPSVN_VERSION_REQUEST",'<?xml version="1.0" encoding="utf-8"?><propfind xmlns="DAV:"><prop><checked-in xmlns="DAV:"/></prop></propfind>');
define("PHPSVN_LOGS_REQUEST",'<?xml version="1.0" encoding="utf-8"?> <S:log-report xmlns:S="svn:"> <S:start-revision>%d</S:start-revision><S:end-revision>%d</S:end-revision><S:path></S:path><S:discover-changed-paths/></S:log-report>');

define("SVN_LAST_MODIFIED","lp1:getlastmodified");
define("SVN_URL","D:href");
define("SVN_RELATIVE_URL","lp3:baseline-relative-path");
define("SVN_FILE_ID","lp3:repository-uuid");
define("SVN_STATUS","D:status");
define("SVN_IN_FILE","D:propstat");
define("SVN_FILE","D:response");

define("SVN_LOGS_BEGINGS","S:log-item");
define("SVN_LOGS_VERSION","D:version-name");
define("SVN_LOGS_AUTHOR","D:creator-displayname");
define("SVN_LOGS_DATE","S:date");

// file changes. Note that we grouping ALL changed files together,
// so we will list deleted and renamed files here as well
define("SVN_LOGS_MODIFIED_FILES","S:modified-path");
define("SVN_LOGS_ADDED_FILES","S:added-path");
define("SVN_LOGS_DELETED_FILES","S:deleted-path");
define("SVN_LOGS_RENAMED_FILES","S:replaced-path");

define("SVN_LOGS_COMMENT","D:comment");

define("NOT_FOUND", 2);
define("AUTH_REQUIRED", 3);
define("UNKNOWN_ERROR",4);
define("NO_ERROR",1)
?>
