<?php
/*	ALL SQL DEALING FUNCTIONS ARE DECLARED IN sqlfunk.php
 *	
 * function get_manager() used for:
 * 			-handle get/post messages regarding SQL module manipulation
 * 			-adding new modules
 * 			-list a table with registered modules with following buttons for each line
 * 					-enable/disable module
 * 					-configure module
 * 					-delete module
 *
 * SQL MODULE structure:
 * 'HW' -database containing:
 * >> -'modules' - table containing:    << 
 *     - module_name - text collumn    - contains module ID that matches the hardcoded module ID (EEPROM), the commands are based on module ID recognition 
 *     - module_type - text collumn    - contains the module type (STANDARD/PID/etc.)
 *     - enabled     - BOOL collumn    - if module is enabled, it is inserted into the servers watchlist for requests
 *     - request     - BOOL collumn    - if request is changed to 1, it means that requires attention for an action, the server will put it to 0 after it handles its request
 * 	   - description - text collumn    - a short description of the module (EX: wall socket in the kitchen..)
 *	   - date_added  - DATE collumn    - the date the module was added
 *  
 * last edited 11.9.16 A.Leo. 
 * 		- added following collumns to table 'modules':
 * 			-'request',
 * 			-'description', 
 * 			-'date_added',
 * 
 * 		- added following functions
 * 			- update_pin(), 
 * 			- del_pins(), 
 * 			- add_pin(),
 *          - list_module_config(),
 * 			- update_module(...) 
*/			



#<=============================================================== HTML TABLE FORM - for ADD MODULE 
#
# -lists the table head
# -prints the textboxes for module name and type
# -prints the submit button for 'add module' function
#
function add_module_form()
{
		?>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Module ID</th>
				<th>Type</th>
				<th>Description</th>
				<th>Action</th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
	</thead>
	<form action="" method="get">
			<tr>
				<td><input type="text" name="module_ID" value="mod01"/></td>
				<td><input type="text" name="module_type" value="standard"/></td>
				<td><input type="text" name="module_description" value="description"/></td>
				<td><input class="btn btn-success" type="submit" value="Add new module" name="button"/></td>
				<td></td>
				<td></td>
				<td></td>
			</tr>
	</form>
	<?php	
}
#</=====================================================================



#<=============================================================== GET / POST MESSAGE PROCESSOR 
# -get_manager() should be the only function called from sqlfunk.php from external pages
# -gets the previous button press and decide wich action is required
# -all messages except the 'config' message leads to list_modules() after message was processed
# -message 'config' leads to list_module_config() that displays all configurable variables of the selected module
#
function get_manager()
{
	#----------------- CALL THE FUNCTION THAT ADDS NEW MODULE TO SQL
	if(isset($_GET['button'])=='Add') add_module($_GET['module_ID'],$_GET['module_type'],$_GET['module_description']);

	#----------------- CALL THE FUNCTION THAT DELETES A MODULE FROM SQL
	if(isset($_GET['delete'])) del_module($_GET['delete']);
	
	#----------------- CALL THE FUNCTION THAT ENABLES A MODULE FROM SQL
	if(isset($_GET['enable'])) denable($_GET['enable'],1);
	
	#----------------- CALL THE FUNCTION THAT DISABLES A MODULE FROM SQL
	if(isset($_GET['disable'])) denable($_GET['disable'],0);

	#----------------- CALL THE FUNCTION THAT UPDATES A MODULE FROM SQL
	if(isset($_GET['update_module_info_button']))update_module($_GET['config'],$_GET['module_type'],$_GET['module_description']);

	#----------------- CALL THE FUNCTION THAT UPDATES A CERTAIN MODULE PIN FROM SQL
	if(isset($_GET['update_module'])) update_pin($_GET['update_module'],$_GET['pin_no'],$_GET['pin_mode'],$_GET['pin_state'],$_GET['pin_description']);   
	#echo "updating module ".$_GET['update_module']."<br>";
	
	
	#----------------- CALL THE FUNCTION THAT CONFIGURES A MODULE IN SQL
	if(isset($_GET['config'])) 
		 list_module_config($_GET['config']);
	else
	{
		 add_module_form();
		 list_modules();
	}
}

#<================================================================================================= UPDATE A MODULE - WITH NEW INFO FROM THE CONFIG MENU
#
function update_module($_1module_name,$_mod_type,$_mod_description)
{
	$conn=sql_connect();
	$query=sprintf("UPDATE `modules` SET `module_type`='%s' ,`description`='%s' WHERE `module_name`='%s'",$_mod_type,$_mod_description,$_1module_name);
	if ($conn->query($query) === TRUE) 
	{	#echo "Module ".$_1module_name." updated successfully <br>";
	}
	else 
		echo "Error: " . $sql . "<br>" . $conn->error;
	sql_disconnect($conn);
}
#=============================================================================================================



#<================================================================================================= UPDATE A PIN - FROM A MODULE
#
function update_pin($_1module_name,$_pin_no,$_pin_mode,$_pin_state,$_pin_description)
{
	$conn=sql_connect();
	$query=sprintf("UPDATE `module_pins` SET `pin_mode`='%s' ,`state`='%s' , `description`='%s', `request`='1' WHERE `pin_no`='%s' AND `module_owner`='%s'",
											  $_pin_mode,     $_pin_state,  $_pin_description,      $_pin_no,         $_1module_name);
	if ($conn->query($query) === TRUE) 
	{	#echo "Module ".$_1module_name." updated successfully <br>";
		$query=sprintf("UPDATE `modules` SET `request`='1' WHERE module_name='%s'",$_1module_name);
		$conn->query($query);
	}
	else 
		echo "Error: " . $sql . "<br>" . $conn->error;
	sql_disconnect($conn);
}
#=============================================================================================================




#<=========================================================================== UPDATE A MODULE - ENABLE / DISABLE A MODULE IN SQL
#
# function denable($_mod_name,$_state) is called using 2 parameters
# updates the $_state (BOOLEAN) column in $_mod_name table
# sets a module true or false
# Example of usage: denable("mod01",1);
function denable($_mod_name,$_state)
{
	$conn=sql_connect();
	$query=sprintf("UPDATE `modules` SET `enabled`='%s' WHERE module_name='%s'",$_state,$_mod_name);
		
	if ($conn->query($query) === TRUE) 
		echo "Module enabled successfully";
	else 
		echo "Error: " . $sql . "<br>" . $conn->error;
	sql_disconnect($conn);
}
#=============================================================================================================





#<============================================================== DELETE ALL PINS FOR THE DELETED MODULE IN SQL
#
# EX. of use: del_pins('mod01')   'will delete all pins with module owner mod01
function del_pins($_1module_name)
{
	$conn=sql_connect();
	$query=sprintf("DELETE FROM module_pins WHERE module_owner='%s'",$_1module_name);
		if ($conn->query($query) === TRUE) {
			echo "PINs deleted successfully";
		}else 
		{
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	sql_disconnect($conn);		
}




#<====================================================================== CREATE A PIN FOR NEW MODULE ADDED IN SQL
#
# -ADD 1 PIN TO A NEWLY CREATED MODULE
# -by DEFAULT all newly created pins are OUTPUT, LOW, without description
# -each PIN will have a module owner
# EX. of use: add_pin('mod01',1) - will create a pin no.1 for mod01
function add_pin($_1module_name,$_pin_no)
{
	$conn=sql_connect();
	$query=sprintf("INSERT INTO module_pins (module_owner,pin_no,pin_mode,state,request,description) 
					VALUES ('%s',%s,'OUTPUT','0','0','')",$_1module_name,$_pin_no);
		if ($conn->query($query) === TRUE) {
			#echo "New PIN created successfully <br>";
		}else 
		{
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	
	sql_disconnect($conn);		
}




#<======================================================================================= CONFIGURE MODULE IN SQL
#
# -lists are editable collumns of a module
function list_module_config($_1module_name)
{
	
	
	?>
	
	<form action="" method="get">
		<input class="btn btn-success" type="submit" value="BACK to Modules" name="back_to_modules_button"/>
	</form>
	<?php
	echo "Configuring module ".$_1module_name;
	$conn=sql_connect();
	?>

	<?php
		
	$conn1=sql_connect();
			?>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>Module ID</th>
				<th>Type</th>
				<th>Description</th>
				<th>Action</th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
	</thead>
	<?php
	$query=sprintf("SELECT * FROM `modules` WHERE `module_name`='%s'",$_1module_name);
	$result = $conn1->query($query);
	if ($result->num_rows > 0) 
	{
		?><form action="" method="get"><?php
		while($row = $result->fetch_assoc()) 
		{	
			?>
			<tr>
				<td><?php echo $row['module_name'];?></td>
				<td>
					<input type="hidden" name="config" value="<?php echo $row['module_name'];?>">
					<input type="text" name="module_type" value="<?php echo $row['module_type'];?>"/>
				</td>
				<td>
					<input type="text" name="module_description" value="<?php echo $row['description'];?>"/>
				</td>	
				<td>
					<input class="btn btn-success" type="submit" value="UPDATE" name="update_module_info_button"/>
				</td>			
				<th></th>
				<th></th>
				<th></th>		
			
			
			</tr>
		<?php }
		?></form></table><?php
	}

	?>
	<table class="table table-striped">
	<thead>
		<tr>
			<th>Owned by</th>	
			<th>PIN No.</th>
			<th>PIN MODE</th>
			<th>STATE</th>
			<th>DESCRIPTION</th>
			<th>REQUESTED</th>
			<TH>UPDATE</TH>
		</tr>
	</thead>	
	<?php
	$query=sprintf("SELECT * FROM `module_pins` WHERE `module_owner`='%s'",$_1module_name);
	$result = $conn->query($query);
	if ($result->num_rows > 0) 
	{
		
		while($row = $result->fetch_assoc()) 
		{	
			?>
			<form action="" method="get">
				<input type="hidden" value="<?php echo $row['module_owner'];?>" name="update_module">
				<input type="hidden" value="<?php echo $row['pin_no'];?>" name="pin_no">
				<input type="hidden" name="config" value="<?php echo $row['module_owner'];?>">
				<tr>
					<td><?php echo $row['module_owner'];?></td>
					<td><?php echo $row['pin_no'];?></td>					
					<td><input type="text" name="pin_mode" value="<?php echo $row['pin_mode'];?>"/></td>
					<td><input type="text" name="pin_state" value="<?php echo $row['state'];?>"/></td>
					<td><input type="text" name="pin_description" value="<?php echo $row['description'];?>"/></td>
					<td><?php echo $row['request'];?></td>
					<td>
						<input class="btn btn-success" type="submit" value="UPDATE" name="config_button"/>
					</td>
				</tr>
			</form>
			<?php
		}
	}
}
#</================================================================================================================


	
	
	
	
	
#<=========================================================================================== ADD NEW MODULE TO SQL
function add_module($_1module_name,$_module_type,$_module_description)
{
	if(isset($_GET['button']))
	{
		$conn=sql_connect();
		$timestamp = date('d-m-y');
		$query=sprintf("INSERT INTO modules (module_name,module_type,description,date_added,enabled,request) 
					    VALUES ('%s','%s','%s','%s',false,false)",$_1module_name,$_module_type,$_module_description,$timestamp);
		if ($conn->query($query) === TRUE) {
			echo "New Module added successfully";
			
			#--------------------- CREATE 13 PINS FOR THE NEW MODULE
			for($i=0;$i<=13;$i++)
				add_pin($_1module_name,$i);
		}else 
		{
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}
	sql_disconnect($conn);
	
}
#</==================================================================================================================



#<============================================================================================ DELETE MODULE FROM SQL
function del_module($_1module_name)
{
	if(isset($_GET['delete']))
	{
		$conn=sql_connect();
		$query=sprintf("DELETE FROM modules WHERE module_name='%s'",$_1module_name);
		
		
		if ($conn->query($query) === TRUE) {
			echo "Module deleted successfully<br>";
		}else 
		{
			echo "Error: " . $sql . "<br>" . $conn->error;
		}
	}
	sql_disconnect($conn);
	del_pins($_1module_name);
	
}
#</===================================================================================================================









#<======================================================================================= LIST ALL MODULES FROM SQL
#
# -lists head of a table,
# -each row will be listed with action buttons (DISABLE/ENABLE,CONFIGURE,DELETE)
function list_modules()
{	$conn=sql_connect();
	
	#<====================html table head
	?>
	<table class="table table-striped">
		<thead>
			<tr>
				<th>No.</th>
				<th>Enabled</th>				
				<th>Module ID</th>
				<th>Type</th>
				<th>Description</th>
				<th>Date added</th>
				<th>Waits processing</th>
				<th>Configure</th>
				<th>Delete</th>

			</tr>
	</thead>
	<?php /*
		<form action="" method="get">
			<tr>
				<td><input type="text" name="IO" value="mod01"/></td>
				<td><input type="text" name="state" value="standard"/></td>
				<td><input type="text" name="Analog_value" value="active"/></td>
				<td><input type="submit" value="Add" name="button"/></td>
			</tr>
		</form>
		*/
	?> 

    <?php 
    #</==================== html table head ?>
<?php
	$query="SELECT * FROM `modules`";
	$result = $conn->query($query);
	if ($result->num_rows > 0) 
	{
		$_no=1;
		while($row = $result->fetch_assoc()) 
		{
			?>	
			<tr>
				<td><?php echo $_no;$_no++;?></td>
				
				<td>
					<form action="" method="get">
						<input type="hidden" name="<?php if($row['enabled']==0) echo "enable";else echo "disable";?>" value="<?php echo $row['module_name'];?>">
						<input type="submit" class="<?php if($row['enabled']==1) echo "btn btn-success";else echo "btn btn-danger";?>"  name="enabled" value="
						<?php if ($row['enabled']==0)echo "disabled";
								else
							  echo "enabled  ";
						?>">
					</form>
				</td>
				<td><?php echo $row['module_name'];?></td>
				<td><?php echo $row['module_type'];?></td>
				<td><?php echo $row['description'];?></td>
				<td><?php echo $row['date_added'];?></td>
				<td><?php if($row['request']==1) echo "true";else echo "false";?></td>
				<td>
				 <form action="" method="get">
					<input type="hidden" name="config" value="<?php echo $row['module_name'];?>">
						<button type="submit" class="btn btn-default" value="<?php echo $row['module_name'];?>" aria-label="Left Align">
							<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>
						</button> </td>
				 </form>
				<td>
					<form action="" method="get">
						<input type="hidden" name="delete" value="<?php echo $row['module_name'];?>">
						<button type="submit" class="btn btn-danger" value="<?php echo $row['module_name'];?>" aria-label="Left Align">
							<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
						</button> 
					
					</form>
				</td>
			  </tr>
			  <?php
		}	
	}
	else 
		echo "No modules installed:". $conn->error;
	sql_disconnect($conn);
	?>	</table> <?php
}
#</================================================================================================== LIST ALL MODULES FROM SQL 








#</================================================================================================== SQL CONNECT ROUTINE
# only usable for HW database
# to be edited for general use in the near future
# use as follows:
#
# $conn=sql_connect()
#
function sql_connect()
{
	$servername="localhost"; 
	$username="root";
	$password="root";
	$dbname="HW";
	$conn = new mysqli($servername, $username, $password, $dbname);
	return $conn;
}
#</================================================================================================== SQL CONNECT ROUTINE






#</================================================================================================== SQL DISCONNECT ROUTINE
function sql_disconnect($_conn)
{
	$_conn->close();
}

?>
