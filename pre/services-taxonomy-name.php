<?php
$override = 1;

$Query = "SELECT ";

// loop through each property and build fields
$field_string = "";
foreach($schema_properties as $field => $value)
	{
	if(isset($value['type']) && $value['type'] != 'array')
		{		
		$type = $value['type'];
		$field_string .= 's.' . $field . ",";
		}
	else
		{			
		// Deal With Array	
		}
	}
$field_string = substr($field_string,0,strlen($field_string)-1);
$Query .= $field_string;

$Query .= " FROM holmes_county_hsda.service s";
$Query .= " JOIN holmes_county_hsda_taxonomy.service_taxonomy st ON s.id = st.service_id";
$Query .= " JOIN holmes_county_hsda_taxonomy.taxonomy t ON t.id = st.taxonomy_id";

// Build the Query
$where = "";
$sorting = "";
$paging = "";
$page = 0;
$per_page = 25;
foreach($parameters as $parameter)
	{
	// Order
	if($parameter['name']=='sortby')
		{
		if(isset($_get['sortby']))
			{			
			$sortby = $_get['sortby'];
			if(isset($_get['order']))
				{
				$order = $_get['order'];
				}
			else
				{
				$order = "asc";
				}
			$sorting = $sortby . " " . $order;	
			}
		}

	// Pagination
	if($parameter['name']=='page')
		{
		if(isset($_get['page']))			
			{
			$page = $_get['page'];
			if(isset($_get['per_page']))
				{
				$per_page = $_get['per_page'];
				}
			}
		$paging = $page . "," . $per_page;		
		}

	}

$Query .= " WHERE t.name = '" . $id . "'";


if($sorting != '')
	{
	$Query .= " ORDER BY " . $sorting;
	}
	
$Pagination_Query = $Query;	

if($paging!='')
	{
	$Query .= " LIMIT " . $paging;
	}

//echo $Query . "<br />";
$results = $conn->query($Query);
if(count($results) > 0)
	{
	foreach ($results as $row)
		{
		$F = array();
		$core_resource_id = '';
		foreach($schema_properties as $field => $value)
			{
				
			if(isset($value['type']) && $value['type'] != 'array')
				{			
				$type = $value['type'];
				$F[$field] = $row[$field];
				
				if($field=='id')
					{
					$core_resource_id = $row[$field];	
					}
				}
			else
				{			
					
				$path_count_array = explode("/",$route);	
				$path_count = count($path_count_array);	
				$core_path = $path_count_array[1];
				$core_path = substr($core_path,0,strlen($core_path)-1);
				//echo "path: " . $core_path . "<br />";
				//echo "path count: " . $path_count . "<br />";				
							
				$sub_schema_ref = $value['items']['$ref'];
				$sub_schema = str_replace("#/definitions/","",$sub_schema_ref);
				$sub_schema_properties = $definitions[$sub_schema]['properties'];
				//echo $sub_schema . "\n";
				//var_dump($sub_schema_properties);
				
				$sub_query = "SELECT ";
				
				// loop through each property and build fields
				$field_string = "";
				foreach($sub_schema_properties as $sub_field_1 => $sub_value_1)
					{
					$type = $sub_value_1['type'];
					$field_string .= $sub_field_1 . ",";
					}
				$field_string = substr($field_string,0,strlen($field_string)-1);
				$sub_query .= $field_string;
				
				$sub_query .= " FROM " . $sub_schema;
				
				$sub_query .= " WHERE " . $core_path . "_id = '" . $core_resource_id . "'";	
				//	echo $sub_query . "/n";
				
				$sub_array = array();
				foreach ($conn->query($sub_query) as $sub_row)
					{	
					$a = array();	
					foreach($sub_schema_properties as $sub_field_2 => $sub_value_2)
						{
						$a[$sub_field_2] = $sub_row[$sub_field_2];
						}
					array_push($sub_array,$a);
					}
					
				$F[$field] = $sub_array;
				
				}			
			}
		array_push($ReturnObject, $F);
		}
	}


?>