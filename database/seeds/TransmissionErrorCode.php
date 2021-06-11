<?php

use Illuminate\Database\Seeder;

class TransmissionErrorCode extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('transmission_error_code')->insert([
            'error_code' => '00000','error_message' => 'EMPTY TRANSMISSION ACCEPTED'
        ]);

        DB::table('transmission_error_code')->insert([
        	'error_code' => '00001','error_message' => ' DUPLICATE TRANS HEADER'
        ]
    );

        DB::table('transmission_error_code')->insert([
         	'error_code' => '00002','error_message' => 'UNEXPECTED END OF INPUT'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00003','error_message' => 'TRANS. HEADER DATE NOT TODAY '
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
             'error_code' => '00004','error_message' => 'CLIENT / SERVICE CONTROL REC NOT FOUND / CLIENT CODE INVALID'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
             'error_code' => '00005','error_message' => 'TRANS. HEADER REC STATUS INVALID'
         ]
         
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00006','error_message' => 'TRANS. HEADER GEN-NO NOT NUMERIC/ TRANS. NUMBER NOT "TEST" - CHECK BYPASSED/ TRANS. NUMBER NOT NEXT IN SEQUENCE'
         ]
    );

        DB::table('transmission_error_code')->insert(
         
         [
         	'error_code' => '00007','error_message' =>' TRANS. HEADER DEST. NOT 00000'
         ]
         
    );

        DB::table('transmission_error_code')->insert(
        [
             'error_code' => '00008','error_message' => 'TRANS. RECORD-ID INVALID (NOT NUMERIC)'
         ]
         
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00009','error_message' => 'TRANS. RECORD-ID nnn NOT FOUND'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00010','error_message' => 'RECORDS AFTER TRANS TRAILER'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00011','error_message' => 'TRANS. TRAILER MISSING'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
             'error_code' => '00012','error_message' => 'TRANS. TRAILER REC. COUNT INVALID'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00013','error_message' => 'SERVICE nnn-xxx NOT ALLOWED'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00014','error_message' => 'NO LIVE DATA WAS ACCEPTED/NO LIVE DATA WAS TRANSMITTED/ NO TRANSMISSION CONTENTS WERE ACCEPTED'
         ]
    );

        DB::table('transmission_error_code')->insert(
         [
         	'error_code' => '00019','error_message' => ' SERVICE (REC-ID nnn) OUT OF SEQUENCE'
         ]
    );
    }
}
