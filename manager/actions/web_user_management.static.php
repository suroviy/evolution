<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!$modx->hasPermission('edit_web_user')) {
	$modx->webAlertAndQuit($_lang["error_no_privileges"]);
}

// initialize page view state - the $_PAGE object
$modx->getManagerApi()->initPageViewState();

// get and save search string
if($_REQUEST['op'] == 'reset') {
	$query = '';
	$_PAGE['vs']['search'] = '';
} else {
	$query = isset($_REQUEST['search']) ? $_REQUEST['search'] : $_PAGE['vs']['search'];
	$sqlQuery = $modx->getDatabase()->escape($query);
	$_PAGE['vs']['search'] = $query;
}

// get & save listmode
$listmode = isset($_REQUEST['listmode']) ? $_REQUEST['listmode'] : $_PAGE['vs']['lm'];
$_PAGE['vs']['lm'] = $listmode;


// context menu
$cm = new \EvolutionCMS\Support\ContextMenu("cntxm", 150);
$cm->addItem($_lang["edit"], "js:menuAction(1)", $_style["icon_edit"], (!$modx->hasPermission('edit_user') ? 1 : 0));
$cm->addItem($_lang["delete"], "js:menuAction(2)", $_style["icon_trash"], (!$modx->hasPermission('delete_user') ? 1 : 0));
echo $cm->render();

?>
<script language="JavaScript" type="text/javascript">
	function searchResource() {
		document.resource.op.value = "srch";
		document.resource.submit();
	};

	function resetSearch() {
		document.resource.search.value = '';
		document.resource.op.value = "reset";
		document.resource.submit();
	};

	function changeListMode() {
		var m = parseInt(document.resource.listmode.value) ? 1 : 0;
		if(m) document.resource.listmode.value = 0;
		else document.resource.listmode.value = 1;
		document.resource.submit();
	};

	var selectedItem;
	var contextm = <?= $cm->getClientScriptObject() ?>;

	function showContentMenu(id, e) {
		selectedItem = id;
		contextm.style.left = (e.pageX || (e.clientX + (document.documentElement.scrollLeft || document.body.scrollLeft))) + "px";
		contextm.style.top = (e.pageY || (e.clientY + (document.documentElement.scrollTop || document.body.scrollTop))) + "px";
		contextm.style.visibility = "visible";
		e.cancelBubble = true;
		return false;
	};

	function menuAction(a) {
		var id = selectedItem;
		switch(a) {
			case 1:		// edit
				window.location.href = 'index.php?a=88&id=' + id;
				break;
			case 2:		// delete
				if(confirm("<?= $_lang['confirm_delete_user'] ?>") === true) {
					window.location.href = 'index.php?a=90&id=' + id;
				}
				break;
		}
	}

	document.addEventListener('click', function() {
		contextm.style.visibility = "hidden";
	});

	document.addEventListener('DOMContentLoaded', function() {
		var h1help = document.querySelector('h1 > .help');
		h1help.onclick = function() {
			document.querySelector('.element-edit-message').classList.toggle('show')
		}
	});

</script>
<form name="resource" method="post">
	<input type="hidden" name="id" value="<?= $id ?>" />
	<input type="hidden" name="listmode" value="<?= $listmode ?>" />
	<input type="hidden" name="op" value="" />

	<h1>
		<i class="<?= $_style['icon_web_user'] ?>"></i><?= $_lang['web_user_management_title'] ?><i class="<?= $_style['icon_question_circle'] ?> help"></i>
	</h1>

	<div class="container element-edit-message">
		<div class="alert alert-info"><?= $_lang['web_user_management_msg'] ?></div>
	</div>

	<div class="tab-page">
		<div class="container container-body">
            <div class="row searchbar form-group">
                <div class="col-sm-6 input-group">
                    <div class="input-group-btn">
                        <a class="btn btn-success btn-sm" href="index.php?a=87"><i class="<?= $_style['icon_add'] ?>"></i> <?= $_lang['new_web_user'] ?></a>
                    </div>
                </div>
                <div class="col-sm-6 ">
                    <div class="input-group float-right w-auto">
                        <input class="form-control form-control-sm" name="search" type="text" value="<?= $query ?>" placeholder="<?= $_lang["search"] ?>" />
                        <div class="input-group-append">
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["search"] ?>" onclick="searchResource();return false;"><i class="<?= $_style['icon_search'] ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["reset"] ?>" onclick="resetSearch();return false;"><i class="<?= $_style['icon_refresh'] ?>"></i></a>
                            <a class="btn btn-secondary btn-sm" href="javascript:;" title="<?= $_lang["list_mode"] ?>" onclick="changeListMode();return false;"><i class="<?= $_style['icon_table'] ?>"></i></a>
                        </div>
                    </div>
                </div>
            </div>
			<div class="row">
				<div class="table-responsive">
					<?php
					$ds = $modx->getDatabase()->select("wu.id, wu.username, wua.fullname, wua.email, wua.thislogin, wua.logincount, IF(wua.blocked,'{$_lang['yes']}','-') as 'blocked'", $modx->getDatabase()->getFullTableName("web_users") . " wu 
			INNER JOIN " . $modx->getDatabase()->getFullTableName("web_user_attributes") . " wua ON wua.internalKey=wu.id", ($sqlQuery ? "(wu.username LIKE '{$sqlQuery}%') OR (wua.fullname LIKE '%{$sqlQuery}%') OR (wua.email LIKE '%{$sqlQuery}%')" : ""), 'username');
					$grd = new \EvolutionCMS\Support\DataGrid('', $ds, $modx->getConfig('number_of_results')); // set page size to 0 t show all items
					$grd->noRecordMsg = $_lang["no_records_found"];
					$grd->cssClass = "table data";
					$grd->columnHeaderClass = "tableHeader";
					$grd->itemClass = "tableItem";
					$grd->altItemClass = "tableAltItem";
					$grd->fields = "id,username,fullname,email,thislogin,logincount,blocked";
					$grd->columns = $_lang["icon"] . " ," . $_lang["name"] . " ," . $_lang["user_full_name"] . " ," . $_lang["email"] . " ," . $_lang["user_prevlogin"] . " ," . $_lang["user_logincount"] . " ," . $_lang["user_block"];
					$grd->colWidths = "1%,,,,1%,1%,1%";
					$grd->colAligns = "center,,,,right' nowrap='nowrap,right,center";
					$grd->colTypes = "template:<a class='gridRowIcon' href='javascript:;' onclick='return showContentMenu([+id+],event);' title='" . $_lang["click_to_context"] . "'><i class='" . $_style["icon_web_user"] . "'></i></a>||template:<a href='index.php?a=88&id=[+id+]' title='" . $_lang["click_to_edit_title"] . "'>[+value+]</a>||template:[+fullname+]||template:[+email+]||date: " . $modx->toDateFormat('[+thislogin+]', 'formatOnly') .
					" %H:%M";
					if($listmode == '1') {
						$grd->pageSize = 0;
					}
					if($_REQUEST['op'] == 'reset') {
						$grd->pageNumber = 1;
					}
					// render grid
					echo $grd->render();
					?>
				</div>
			</div>
		</div>
	</div>
</form>
