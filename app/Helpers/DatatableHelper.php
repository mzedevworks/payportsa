<?php
namespace App\Helpers;

use App\Model\CompanyInformation;
use Illuminate\Support\Facades\Mail;

class DatatableHelper
{

	/**
	 * Create the data output array for the DataTables rows
	 *
	 *  @param  array $columns Column information array
	 *  @param  array $data    Data from the SQL get
	 *  @return array          Formatted data in a row based format
	 */
	static function data_output ( $columns, $data )
	{
		$data=json_decode(json_encode($data),true) ;
		$out = array();

		for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
			$row = array();

			for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
				$column = $columns[$j];

				// Is there a formatter?
				if ( isset( $column['formatter'] ) ) {
					if(isset($column['feildAlias'])){
						$row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['feildAlias'] ], $data[$i] );	
					}elseif(isset($column['db'])){
						$row[ $column['dt'] ] = $column['formatter']( $data[$i][ $column['db'] ], $data[$i] );	
					}else{
						$row[ $column['dt'] ] = $column['formatter']( "", $data[$i] );
					}
					
				}
				else {
					if(isset($columns[$j]['feildAlias'])){
						$row[ $column['dt'] ] = $data[$i][ $columns[$j]['feildAlias'] ];	
					}else{
						$row[ $column['dt'] ] = $data[$i][ $columns[$j]['db'] ];
					}
					
				}
			}

			$out[] = $row;
		}

		return $out;
	}

	/**
	 * Paging
	 *
	 * Construct the LIMIT clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL limit clause
	 */
	static function limit ( $request, $columns )
	{
		$limit = '';

		if ( isset($request['start']) && $request['length'] != -1 ) {
			$limit = "LIMIT ".intval($request['start']).", ".intval($request['length']);
		}

		return $limit;
	}

	/**
	 * Ordering
	 *
	 * Construct the ORDER BY clause for server-side processing SQL query
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @return string SQL order by clause
	 */
	static function order ( $request, $columns )
	{
		$order = '';

		if ( isset($request['order']) && count($request['order']) ) {
			$orderBy = array();
			$dtColumns = array_column( $columns, 'dt' );

			for ( $i=0, $ien=count($request['order']) ; $i<$ien ; $i++ ) {
				// Convert the column index into the column data property
				$columnIdx = intval($request['order'][$i]['column']);
				$requestColumn = $request['columns'][$columnIdx];

				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['orderable'] == 'true' ) {
					$dir = $request['order'][$i]['dir'] === 'asc' ?
						'ASC' :
						'DESC';

					
					if(isset($column['dbAlias'])){
						$orderBy[] = $column['dbAlias'].'.`'.$column['db'].'` '.$dir;
					}else{
						$orderBy[] = '`'.$column['db'].'` '.$dir;
					}
				}
			}

			$order = implode(', ', $orderBy);
		}

		return $order;
	}

	/**
	 * Searching / Filtering
	 *
	 * Construct the WHERE clause for server-side processing SQL query.
	 *
	 * NOTE this does not match the built-in DataTables filtering which does it
	 * word by word on any field. It's possible to do here performance on large
	 * databases would be very poor
	 *
	 *  @param  array $request Data sent to server by DataTables
	 *  @param  array $columns Column information array
	 *  @param  array $bindings Array of values for PDO bindings, used in the
	 *    sql_exec() function
	 *  @return string SQL where clause
	 */
	static function filter ( $request, $columns, &$bindings )
	{
		$globalSearch = array();
		$columnSearch = array();
		$dtColumns = array_column( $columns, 'dt' );

		if ( isset($request['search']) && $request['search']['value'] != '' ) {
			$str = $request['search']['value'];

			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				if ( $requestColumn['searchable'] == 'true' ) {
					if(array_key_exists('filterfrom', $column)){
						$bindings[] ="%".$column['filterfrom']($str)."%";
					}else{
						$bindings[] ="%".$str."%";
					}
					if(isset($column['dbAlias'])){
						$globalSearch[] = "`".$column['dbAlias']."`.`".$column['db']."` LIKE ?";	
					}else{
						$globalSearch[] = "`".$column['db']."` LIKE ?";	
					}
					
				}
			}
		}

		// Individual column filtering
		if ( isset( $request['columns'] ) ) {
			for ( $i=0, $ien=count($request['columns']) ; $i<$ien ; $i++ ) {
				$requestColumn = $request['columns'][$i];
				$columnIdx = array_search( $requestColumn['data'], $dtColumns );
				$column = $columns[ $columnIdx ];

				$str = $requestColumn['search']['value'];

				if ( $requestColumn['searchable'] == 'true' &&
				 $str != '' ) {
					$bindings[] ="%".$str."%";
					if(isset($column['dbAlias'])){
						$columnSearch[] = "`".$column['dbAlias']."`.`".$column['db']."` LIKE ?";	
					}else{
						$columnSearch[] = "`".$column['db']."` LIKE ?";
					}
					
				}
			}
		}

		// Combine the filters into a single string
		$where = '';

		if ( count( $globalSearch ) ) {
			$where = '('.implode(' OR ', $globalSearch).')';
		}

		if ( count( $columnSearch ) ) {
			$where = $where === '' ?
				implode(' AND ', $columnSearch) :
				$where .' AND '. implode(' AND ', $columnSearch);
		}

		if ( $where !== '' ) {
			$where = $where;
		}

		return $where;
	}




	
	
}

