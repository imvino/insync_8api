<?php
// this must be included on all pages to authenticate the user
require_once($_SERVER['DOCUMENT_ROOT'] . "/auth/authSystem.php");
$permissions = authSystem::ValidateUser();
// end

$title = ": Email Tester";
$breadCrumb = "<h1>Settings <small>Email Tester</small></h1>";
$menuCategory = "settings";

$head = <<<HEAD_WRAP
<!-- HEADER -->
<!-- END HEADER -->
HEAD_WRAP;

include("includes/header.php");

////////////////////////////////////////////////////////////////////////////////
// PAGE PHP
////////////////////////////////////////////////////////////////////////////////

if (empty($permissions["configure"])) {
    echo "<h3>Error: You do not have permission to access this page.</h3>";
    include("includes/footer.php");
    exit;
}

?>

<body>
<html>

<div class="row">
    <p>Test sending of emails from this InSync processor.</p>
</div>

<!-- <h2>PHP Form Validation Example</h2> -->
<p><span class="error" style="color:red">* required field</span></p>
<form method="post" id="form" name="form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">  

	<table>
	<tr>
		<td align="right">Mail To:</td>
		<td align="left"><input type="text" name="to" id="to" value="<?php echo $to;?>" /></td>
		<!-- <td align="left"><input type="text" name="to" id="to" value="manoj.rajkumar@rhythmtraffic.com" /></td> -->
		<td> <span class="error" style="color:red">* <?php echo $toErr;?></span> </td>
	</tr>
	<tr>
      <td align="right">Mail From:</td>
      <td align="left"><input type="text" name="from" id="from" value="<?php echo $from;?>" /></td>
	  <!-- <td align="left"><input type="text" name="from" id="from" value="alerts@mcdot-its.com" /></td> -->
	  <td> <span class="error" style="color:red">* <?php echo $fromErr;?></span> </td>
    </tr>
	<tr>
      <td align="right">Smtp Server:</td>
      <td align="left"><input type="text" name="server" id="server" value="<?php echo $server;?>" /></td>
	  <!-- <td align="left"><input type="text" name="server" id="server" value="smtp-relay.gmail.com" /></td> -->
	  <td> <span class="error" style="color:red">* <?php echo $serverErr;?></span><span class="error" style "color:black">Add DNS Server if not using an IP address for SMTP server to prevent exceptions during send</span> </td>
    </tr>
	<tr>
      <td align="right">Smtp Port:</td>
      <td align="left"><input type="text" name="port" id="port" value="<?php echo $port;?>" /></td>
	  <!-- <td align="left"><input type="text" name="port" id="port" value="25" /></td> -->
	  <td> <span class="error" style="color:red">* <?php echo $portErr;?></span> </td>
    </tr>
	<tr>
      <td align="right">Try All:</td>
      <td align="left"><input type="checkbox" name="tryall" id="tryall" value="<?php echo $tryall;?>" /></td>
	  <td> <span class="error">Test all ports and combinations of authorization and ssl. Auth(yes/no), Ports(25,2525,465,587)<?php echo $portErr;?></span> </td>
    </tr>
	<tr>
      <td align="right">Use Auth:</td>
      <td align="left"><input type="checkbox" name="useauth" id="useauth" value="<?php echo $useauth;?>" /></td>
	  <td> <span class="error">Use authentication when sending email. <?php echo $serverErr;?></span> </td>
    </tr>
	<tr>
      <td align="right">Use Ssl/Tls:</td>
      <td align="left"><input type="checkbox" name="usessl" id="usessl" value="<?php echo $usessl;?>" /></td>
	  <td> <span class="error">Send email using SSL. <?php echo $serverErr;?></span> </td>
    </tr>
	<tr>
      <td align="right">Username:</td>
      <td align="left"><input type="text" name="user" id="user" value="<?php echo $user;?>" /></td>
	  <!-- <td align="left"><input type="text" name="user" id="user" value="james.bley@rhythmtraffic.com" /></td> -->
	  <td> <span class="error"> <?php echo $userErr;?></span> </td>
    </tr>
	<tr>
      <td align="right">Password:</td>
      <td align="left"><input type="text" name="pass" id="pass" value="<?php echo $pass;?>" /></td>
	  <!-- <td align="left"><input type="text" name="pass" id="pass" value="piqcwprbjaoplkmy" /></td> -->
	  <td> <span class="error"> <?php echo $passErr;?></span> </td>
    </tr>
	<tr>
	</table>
</form>

<?php
//echo "<h2>Your Input:</h2>";
//echo $name;
//echo "<br>";
//echo $email;
//echo "<br>";
//echo $website;
//echo "<br>";
//echo $comment;
//echo "<br>";
//echo $gender;

?>

</body>
</html>

    <!-- These need to stay OUTSIDE of the form -->
    <div class="form-horizontal">
        <div class="control-group">
            <div class="controls small">
                <button id="send" class="btn btn-default green">Send Email</button>
                <button id="reset" class="btn btn-default">Reset</button>
            </div>
        </div>   
    </div>

	<div>
		Result: <textarea name="result" id="result" rows="5" cols="10"></textarea>		
	</div>

<!--
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
		alert("Hello");
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
-->
<script type="text/javascript">
    $("#send").button().click(function() {
        //var fieldsArray = $("form").serialize();

		var to = document.getElementById("to").value;
		var from = document.getElementById("from").value;
		var server = document.getElementById("server").value;
		var port = document.getElementById("port").value;
		var tryall = document.getElementById("tryall").checked;
		var useauth = document.getElementById("useauth").checked;
		var usessl = document.getElementById("usessl").checked;
		var user = document.getElementById("user").value;
		var pass = document.getElementById("pass").value;

		if (to != "" && from != "" && server != "")
		{
			var fields = "toadd=" + to + "&fromadd=" + from + "&server=" + server + "&port=" + port + "&tryall=" + tryall
								  + "&useauth=" + useauth + "&usessl=" + usessl + "&user=" + user + "&pass=" + pass;
			//alert(fields);
			$("#result").html("Sending email, please wait...");

			$.post('helpers/emailTesterHelper.php?action=send&' + fields,
				function(data) {
					document.getElementById("result").innerHTML = data;

					//if (data == "Success")
						//popupNotification(data, 3000, "notice");
					//else
					//	popupNotification(data, 3000);
				});
		}
		else
		{
			popupNotification("* Mandatory fields does not have valid data", 3000);
		}
    });

$("#reset").button().click(function() {
			document.getElementById("form").reset();
			document.getElementById("result").innerHTML = "";
    });	

</script>


<?php
include("includes/footer.php")
?>