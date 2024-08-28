<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Rename Phases";
$breadCrumb = "<h1>Settings <small>Rename Phases</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<!-- END HEADER -->
HEAD_WRAP
;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if (empty($permissions["configure"])) {
    echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

require_once("helpers/phaseHelper.php");

$phaseNames = getPhaseNames();

$activePhases = getActivePhases();
?>
<div class="row">
    <p>Note: Inactive phases are not shown.</p>
</div>
<div class="row section">
    <form id="phaseNames" class="form-horizontal">
<?php
foreach($activePhases as $key=>$value)
{
    echo '<div class="control-group">';
        echo '<label class="control-label small" for="phase' . $value . 'long">Phase ' . $value . '</label>';
        echo '<div class="controls small">';
            echo '<input type="text" name="phase' . $value . 'long" class="input-green input-large" placeholder="Phase Name" value="'. $phaseNames[$value]['long'] . '" maxlength="40"/> ';
            echo '<input type="text" name="phase' . $value . 'short" class="input-green" value="' . $phaseNames[$value]['short'] . '" maxlength="6"/>';
        echo '</div>';
    echo '</div>';
}
?>             
    </form>

    <!-- These need to stay OUTSIDE of the form -->
    <div class="form-horizontal">
        <div class="control-group">
            <div class="controls small">
                <button id="save" class="btn btn-default green">Save</button>
                <button id="reset" class="btn btn-default">Reset</button>
            </div>
        </div>   
    </div>
</div>


<div id="dialog-confirm" title="Confirm Reset">
    <div class="warning">
        <p><strong>WARNING:</strong><br/>This will reset all phase names to their defaults.</p>
    </div>
    <p>Are you sure you want to proceed?</p>
</div>

<script>
$(document).ready(function() {
    $("#dialog-confirm").dialog({
        autoOpen: false,
        resizable: false,
        modal: true,
        closeText: '×',
        buttons: {
            "Yes": function() {
                $.post("helpers/phaseHelper.php?action=reset", function(data) {
                    console.log(data);
                    
                    if (data == "Success")
                        location.reload();
                    else {
                        popupNotification("Error: Could not reset phase names!", 2500);
                        $("#dialog-confirm").dialog("close");
                    }
                });
            },
            Cancel: function() {
                $(this).dialog("close");
            }
        }
    });

    $("#save").button().click(function() {
        var phaseNamesArray = $("form").serialize();

        $.post('helpers/phaseHelper.php?action=save&' + phaseNamesArray,
            function(data) {
                if (data == "Success")
                    popupNotification("Saved", 3000, "notice");
                else
                    popupNotification(data, 3000);
            });
    });

    $("#reset").button().click(function() {
        $("#dialog-confirm").dialog("open");
    });
});
</script>

<?php
include("includes/footer.php")
?>