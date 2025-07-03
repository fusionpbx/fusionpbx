
<?php

// The Ringotel Checker
if (!permission_exists("extension_ringotel")) {
    $reject_ringotel = false;
} else {
    $reject_ringotel = true;
    if (!empty($_GET['show']) && $_GET['show'] == "all") {
        $reject_ringotel = false;
    }

    // string to inject to DOM-elements
    $target_id = 'extension_ringotel';
    $target_header = 'Context';

    // table head
    $table_head = "<table style='display: none;'><tr>";
    $table_head .= "<th id='" . $target_id . "'>";
    $table_head .= "Ringotel";
    $table_head .= "</th></tr></table>";

    // render elements (they will be relocated)
    echo $table_head;
}
echo "<style>";
echo "    .ringotel_info {";
echo "        display: flex;";
echo "        flex-direction: row;";
echo "        align-items: center;";
echo "    }";
echo "    .spinner_sync {";
echo "		animation: spinnerSync 1s linear infinite;";
echo "	}";
echo "    @keyframes spinnerSync {";
echo "		0% {";
echo "			transform: rotate(0deg);";
echo "		}";
echo "";
echo "		25% {";
echo "			transform: rotate(90deg);";
echo "		}";
echo "";
echo "		50% {";
echo "			transform: rotate(180deg);";
echo "		}";
echo "";
echo "		75% {";
echo "			transform: rotate(270deg);";
echo "		}";
echo "";
echo "		100% {";
echo "			transform: rotate(360deg);";
echo "		}";
echo "	}";
echo "    .popover-div {";
echo "        cursor: pointer;";
echo "        position: relative;";
echo "    }";
echo "    .popover-content {";
echo "        display: none;";
echo "        position: absolute;";
echo "        margin-top: 0;";
echo "        padding: 5px 10px;";
echo "        background-color: #fff;";
echo "        border: 1px solid #dee2e6;";
echo "        border-radius: 5px;";
echo "        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);";
echo "        z-index: 1000;";
echo "        font-family: arial;";
echo "        flex-direction: column;";
echo "        gap: 0rem;";
echo "        width: max-content;";
echo "        color: black;";
echo "        transform: translateX(55px) translateY(5px);";
echo "        line-height: 13pt;";
echo "        font-weight: 800;";
echo "    }";
echo "    div.card {";
echo "        overflow-x: unset;";
echo "    }";
echo "</style>";
?>
<script>
    // if we have a table
    // the method and target to inject
    if ('<?php echo boolval($reject_ringotel) ?>') {

        const getOrganizationId = async () => await $.ajax({
        		url: "/app/ringotel/service.php?method=getOrganization",
        		type: "get",
        		cache: true
            }).then((response) => {
        		const { result } = JSON.parse(response.replaceAll("\\", ""));
                return result?.id;
            });
        
        const getRingotelUsers = async (orgid) => 
            await $.ajax({
        		url: "/app/ringotel/service.php?method=getUsers",
        		type: "get",
        		cache: true,
        		data: {
        			orgid,
        		}
        	}).then((response) => {
        		const { result } = JSON.parse(response.replaceAll("\\", ""));
                return result;
        	});
        
        
        function showExtPopover(event) {
            const popover = event.currentTarget.querySelector('.popover-content');
            popover.style.display = 'flex';
        };

        function hideExtPopover(event) {
            const popover = event.currentTarget.querySelector('.popover-content');
            popover.style.display = 'none';
        };

        const initRingotelExtensions = async () => {

            // Inject The Column Head
            const headings = document.evaluate('//th[contains(., "<?php echo $target_header ?>")]', document, null, XPathResult.ANY_TYPE, null);
            const thisHeading = headings.iterateNext();
            thisHeading.before(document.getElementById('<?php echo $target_id; ?>'));
            const shift = [...document.getElementById('<?php echo $target_id; ?>').parentNode.children].map((el, k) => {
                if (el.id == '<?php echo $target_id; ?>') {
                    return k;
                }
            }).filter(el => el)[0];
        
            // Loading For Rows For Current Column
            [...document.getElementById('<?php echo $target_id; ?>').parentNode.parentNode.children].map((td, k) => {
                    if (k != 0) {
                        document.getElementById('<?php echo $target_id ?>_'+k)?.parentNode.remove();
                        const default_f = '<td><div id="<?php echo $target_id ?>_'+k+'" style="margin-left:2rem;"><i class="fas fa-spinner spinner_sync"></i></div></td>';
                        td.children[shift - 1].insertAdjacentHTML('afterend', default_f);
                    }
                });

            // Get Organization For Current Domain
            const orgId = await getOrganizationId();
            let extensions = [];
            if (orgId) {
                // Get Ringotel Users for Current Domain
	            let parksUserExtensions = await getRingotelUsers(orgId);
                // Split
	        	const regexp = /[\+*]/; // Parks param detect
                // Prepare
	        	extensions = parksUserExtensions.map((ext) => {
                    const extension = {};
                    if (ext.extension.match(regexp)) {
                        extension.type = 'park';
                    }
                    if ((!ext.extension.match(regexp) && ext.status === 1)) {
                        extension.type = 'user';
                    }
                    if ((!ext.extension.match(regexp) && ext.status !== 1)) {
                        extension.type = 'extension';
                    }
                    extension['orgid'] = orgId || null;
                    extension['id'] = ext?.id || null;
                    extension['branchid'] = ext?.branchid || null;
                    extension['extension'] = ext?.extension || null;
                    extension['name'] = ext?.name || null;
                    extension['username'] = ext?.name || null;
                    extension['extension_exists'] = ext?.extension_exists || null;
                    extension['devs'] = ext?.devs || null;
                    extension['info'] = ext?.info || null;
                
                    return extension;
                });
            } else {
                [...document.getElementById('<?php echo $target_id; ?>').parentNode.parentNode.children].map((td, k) => {
                    if (k != 0) {
                        document.getElementById('<?php echo $target_id ?>_'+k)?.parentNode.remove();
                        const default_f = '<td><div class="indicator-red" style="margin-left: 2rem;" id="<?php echo $target_id ?>_'+k+'" ></div></td>';
                        td.children[shift - 1].insertAdjacentHTML('afterend', default_f);
                    }
                });
            }

            if (Array.isArray(extensions) && extensions?.length > 0) {
                [...document.getElementById('<?php echo $target_id; ?>').parentNode.parentNode.children].map((tr, k) => {
                    if (k != 0) {
                        document.getElementById('<?php echo $target_id ?>_'+k)?.parentNode.remove();
                        const ext_ = tr?.querySelector('td a')?.innerText;
                        const ext_data = extensions?.find(({ extension }) => extension == ext_);

                        if (ext_data && ext_data?.type == 'user') {
                            let ext_f  = '<td>';
                                ext_f += '  <div class="ringotel_info popover-div" onmouseover="showExtPopover(event)" onmouseout="hideExtPopover(event)"  id="<?php echo $target_id ?>_'+k+'">';
                                ext_f += '      <i class="fa fa-info-circle indicator-green " aria-hidden="true" id="<?php echo $target_id ?>_popup_'+k+'" style="color: rgb(64 217 88);box-shadow:none;">';
                                ext_f += '      </i>';
                                ext_f += '          <div class="popover-content">';
                                ext_f += '              <div style="display: flex;flex-direction: row;align-items: center;">';
                                ext_f += '                   <div style="padding-right: 0.25rem;">Username:</div>';
                                ext_f += '                   <div>'+(ext_data.username||'-')+'</div>';
                                ext_f += '              </div>';
                                ext_f += '              <div style="display: flex;flex-direction: row;align-items: center;">';
                                ext_f += '                   <div style="padding-right: 0.25rem;">Type:</div>';
                                ext_f += '                   <div>'+ext_data.type+'</div>';
                                ext_f += '              </div>';
                                if (ext_data?.info?.email) {
                                    ext_f += '              <div style="display: flex;flex-direction: row;align-items: center;">';
                                    ext_f += '                   <div style="padding-right: 0.25rem;">Email:</div>';
                                    ext_f += '                   <div>'+ext_data.info.email+'</div>';
                                    ext_f += '              </div>';  
                                }
                                if (Array.isArray(ext_data?.devs)) {
                                    for (device of ext_data.devs) {
                                        if (device?.ua) {
                                            ext_f += '              <div style="display: flex;flex-direction: row;align-items: center;">';
                                            ext_f += '                   <div style="padding-right: 0.25rem;">Device:</div>';
                                            ext_f += '                   <div>'+device.ua+'</div>';
                                            ext_f += '              </div>';
                                        }
                                        if (device?.ip) {
                                            ext_f += '              <div style="display: flex;flex-direction: row;align-items: center;">';
                                            ext_f += '                   <div style="padding-right: 0.25rem;">IP:</div>';
                                            ext_f += '                   <div>'+device.ip+'</div>';
                                            ext_f += '              </div>';
                                        }
                                    };  
                                }
                                ext_f += '          </div>';
                                ext_f += `      <div style="text-align: center;margin-left: 0.2rem;" class="ringotel_app" data-id="${ext_data.id}" data-org-id="${ext_data.orgid}" data-branch-id="${ext_data.branchid}">`;
                                ext_f +=            (ext_data?.name||ext_data?.username)
                                ext_f += '      </div>';
                                ext_f += '  </div>';
                                ext_f += '</td>';
                            tr.children[shift - 1].insertAdjacentHTML('afterend', ext_f);
                        } else {
                            let default_f  = '<td>';
                                default_f += `  <div class="${ext_data?.type == 'extension' ? 'indicator-yellow' : 'indicator-red'}" style="margin-left: 2rem;" id="<?php echo $target_id ?>_`+k+`"></div>`;
                                default_f += '</td>';
                            tr.children[shift - 1].insertAdjacentHTML('afterend', default_f);
                        }
                    }
                });
            } else {
                [...document.getElementById('<?php echo $target_id; ?>').parentNode.parentNode.children].map((td, k) => {
                    if (k != 0) {
                        document.getElementById('<?php echo $target_id ?>_'+k)?.parentNode.remove();
                        const default_f = '<td><div class="indicator-red" style="margin-left: 2rem;" id="<?php echo $target_id ?>_'+k+'"></div></td>';
                        td.children[shift - 1].insertAdjacentHTML('afterend', default_f);
                    }
                });
            }
        }

        // init column
        initRingotelExtensions();

        ////////////////////////////////////
        /// Delete Users For Ringotel ///
        ///////////////////////////////////

        // Function to make an async AJAX request
        function deleteUserRequest(orgid, userId) {
            return new Promise((resolve, reject) => {
                $.ajax({
				    url: "/app/ringotel/service.php?method=deleteUser",
				    type: "get",
				    cache: true,
				    data: {
				    	id: userId,
				    	orgid: orgid
				    },
                    success: function(data) {
                        resolve(data);
                    },
                    error: function(error) {
                        reject(error);
                    }
                });
            });
        }

        // Delete Functional For Ringotel
        function deleteTheAppExt() {
            // modal_close(); list_action_set('delete_extension'); list_form_submit('form_list');
            var	checked = false;
            var inputs = document.getElementsByTagName('input');
            var usersData = []; 
            for (var i = 0, max = inputs.length; i < max; i++) {
            	if (inputs[i].type === 'checkbox' && inputs[i].checked == true && Boolean(inputs[i].parentNode.className.indexOf('switch'))) {
            		checked = true;
                    const ringotelExtAppElement = inputs[i].parentNode.parentNode.querySelector('.ringotel_app');

                    if (ringotelExtAppElement) {
			            const userId = ringotelExtAppElement.getAttribute('data-id');
			            const orgid = ringotelExtAppElement.getAttribute('data-org-id');
                        usersData.push({ orgid, userId });
                    }
            	}
            }

            // Deleting Spinner
            document.getElementById('delete_app_button').innerHTML = '';
            document.getElementById('delete_app_button').insertAdjacentHTML('beforeend', '<i class="fas fa-spinner spinner_sync" style="margin-right: 0.4rem;"></i>DELETEING');

            // Array to store promises for each AJAX request
            const requestPromises = usersData.map(({ orgid, userId }) => deleteUserRequest(orgid, userId));

            if (Array.isArray(requestPromises) && requestPromises?.length > 0) {
                Promise.all(requestPromises)
                    .then(results => {
                        list_action_set('delete_extension_voicemail'); 
                        list_form_submit('form_list');
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                    });
            } else {
                modal_close(); 
                list_action_set('delete_extension_voicemail'); 
                list_form_submit('form_list');
            }
        }


        // Delete Element Button
        const button_delete_app_ext = `<?php echo button::create(['type'=>'button','id'=> 'delete_app_button', 'label'=>'Extension & VM & App','icon'=>'user','collapse'=>'never','style'=>'float: right; margin-left: 15px;','onclick'=>"deleteTheAppExt();"]) ?>`;

        const deleteElementModalAction = document.getElementById('modal-delete-options')?.querySelector('.modal-actions');

        // Inject the New Button
        deleteElementModalAction?.insertAdjacentHTML('afterbegin', button_delete_app_ext);

        // CSS Style @Media Injection
        if (window.matchMedia("(min-width: 700px)").matches) {
            if (document.getElementById('modal-delete-options')?.querySelector('.modal-window > div')?.style) {
                document.getElementById('modal-delete-options').querySelector('.modal-window > div').style.width = '600px';
            }
        }

    }


</script>
