<?php

namespace IU\REDCapETL;

use PHPUnit\Framework\TestCase;

use IU\PHPCap\ErrorHandlerInterface;
use IU\PHPCap\PhpCapException;
use IU\PHPCap\RedCapApiConnectionInterface;

use IU\REDCapETL\TestProject;
use IU\REDCapETL\EtlRedCapProject;

/**
 * PHPUnit tests for the RulesGenerator class.
 */
class RulesGeneratorTest extends TestCase
{   


    public function testGenerate()
    {
        $projectInfo['project_id'] = 14;
        $projectInfo['project_title'] = 'ETL_Data';
        $projectInfo['creation_time'] = '2018-04-16 13:53:19';
        $projectInfo['production_time'] =
        $projectInfo['in_production'] = 0;
        $projectInfo['project_language'] = 'English';
        $projectInfo['purpose'] = 0;
        $projectInfo['purpose_other'] =
        $projectInfo['project_notes'] =
        $projectInfo['custom_record_label'] =
        $projectInfo['secondary_unique_field'] =
        $projectInfo['is_longitudinal'] = 0;
        $projectInfo['surveys_enabled'] = 0;
        $projectInfo['scheduling_enabled'] = 0;
        $projectInfo['record_autonumbering_enabled'] = 1;
        $projectInfo['randomization_enabled'] = 0;
        $projectInfo['ddp_enabled'] = 0;
        $projectInfo['project_irb_number'] =
        $projectInfo['project_grant_number'] =
        $projectInfo['project_pi_firstname'] =
        $projectInfo['project_pi_lastname'] =
        $projectInfo['display_today_now_button'] = 1;
        $projectInfo['has_repeating_instruments_or_events'] = 0;

        $instruments['demographics'] = 'Basic Demography Form';
       
        $metadata[0]['field_name'] = 'record_id';
        $metadata[0]['form_name'] = 'demographics';
        $metadata[0]['section_header'] =
        $metadata[0]['field_type'] = 'text';
        $metadata[0]['field_label'] = 'Study ID';
        $metadata[0]['select_choices_or_calculations'] =
        $metadata[0]['field_note'] =
        $metadata[0]['text_validation_type_or_show_slider_number'] =
        $metadata[0]['text_validation_min'] =
        $metadata[0]['text_validation_max'] =
        $metadata[0]['identifier'] =
        $metadata[0]['branching_logic'] =
        $metadata[0]['required_field'] =
        $metadata[0]['custom_alignment'] =
        $metadata[0]['question_number'] =
        $metadata[0]['matrix_group_name'] =
        $metadata[0]['matrix_ranking'] =
        $metadata[0]['field_annotation'] =  

        $metadata[1]['field_name'] = 'first_name';
        $metadata[1]['form_name'] = 'demographics';
        $metadata[1]['section_header'] = 'Contact Information';
        $metadata[1]['field_type'] = 'text';
        $metadata[1]['field_label'] = 'First Name';
        $metadata[1]['select_choices_or_calculations'] =
        $metadata[1]['field_note'] =
        $metadata[1]['text_validation_type_or_show_slider_number'] =
        $metadata[1]['text_validation_min'] =
        $metadata[1]['text_validation_max'] =
        $metadata[1]['identifier'] = 'y';
        $metadata[1]['branching_logic'] =
        $metadata[1]['required_field'] =
        $metadata[1]['custom_alignment'] =
        $metadata[1]['question_number'] =
        $metadata[1]['matrix_group_name'] =
        $metadata[1]['matrix_ranking'] =
        $metadata[1]['field_annotation'] =

        $metadata[2]['field_name'] = 'last_name';
        $metadata[2]['form_name'] = 'demographics';
        $metadata[2]['section_header'] =
        $metadata[2]['field_type'] = 'text';
        $metadata[2]['field_label'] = 'Last Name';
        $metadata[2]['select_choices_or_calculations'] =
        $metadata[2]['field_note'] =
        $metadata[2]['text_validation_type_or_show_slider_number'] =
        $metadata[2]['text_validation_min'] =
        $metadata[2]['text_validation_max'] =
        $metadata[2]['identifier'] = 'y';
        $metadata[2]['branching_logic'] =
        $metadata[2]['required_field'] =
        $metadata[2]['custom_alignment'] =
        $metadata[2]['question_number'] =
        $metadata[2]['matrix_group_name'] =
        $metadata[2]['matrix_ranking'] =
        $metadata[2]['field_annotation'] =

        $metadata[3]['field_name'] = 'address';
        $metadata[3]['form_name'] = 'demographics';
        $metadata[3]['section_header'] =
        $metadata[3]['field_type'] = 'notes';
        $metadata[3]['field_label'] = 'Street, City, State, ZIP';
        $metadata[3]['select_choices_or_calculations'] =
        $metadata[3]['field_note'] =
        $metadata[3]['text_validation_type_or_show_slider_number'] =
        $metadata[3]['text_validation_min'] =
        $metadata[3]['text_validation_max'] =
        $metadata[3]['identifier'] = 'y';
        $metadata[3]['branching_logic'] =
        $metadata[3]['required_field'] =
        $metadata[3]['custom_alignment'] =
        $metadata[3]['question_number'] =
        $metadata[3]['matrix_group_name'] =
        $metadata[3]['matrix_ranking'] =
        $metadata[3]['field_annotation'] =

        $metadata[4]['field_name'] = 'telephone';
        $metadata[4]['form_name'] = 'demographics';
        $metadata[4]['section_header'] =
        $metadata[4]['field_type'] = 'text';
        $metadata[4]['field_label'] = 'Phone number';
        $metadata[4]['select_choices_or_calculations'] =
        $metadata[4]['field_note'] = 'Include Area Code';
        $metadata[4]['text_validation_type_or_show_slider_number'] = 'phone';
        $metadata[4]['text_validation_min'] =
        $metadata[4]['text_validation_max'] =
        $metadata[4]['identifier'] = 'y';
        $metadata[4]['branching_logic'] =
        $metadata[4]['required_field'] =
        $metadata[4]['custom_alignment'] =
        $metadata[4]['question_number'] =
        $metadata[4]['matrix_group_name'] =
        $metadata[4]['matrix_ranking'] =
        $metadata[4]['field_annotation'] =

        $metadata[5]['field_name'] = 'email';
        $metadata[5]['form_name'] = 'demographics';
        $metadata[5]['section_header'] =
        $metadata[5]['field_type'] = 'text';
        $metadata[5]['field_label'] = 'E-mail';
        $metadata[5]['select_choices_or_calculations'] =
        $metadata[5]['field_note'] =
        $metadata[5]['text_validation_type_or_show_slider_number'] = 'email';
        $metadata[5]['text_validation_min'] =
        $metadata[5]['text_validation_max'] =
        $metadata[5]['identifier'] = 'y';
        $metadata[5]['branching_logic'] =
        $metadata[5]['required_field'] =
        $metadata[5]['custom_alignment'] =
        $metadata[5]['question_number'] =
        $metadata[5]['matrix_group_name'] =
        $metadata[5]['matrix_ranking'] =
        $metadata[5]['field_annotation'] =

        $metadata[6]['field_name'] = 'dob';
        $metadata[6]['form_name'] = 'demographics';
        $metadata[6]['section_header'] =
        $metadata[6]['field_type'] = 'text';
        $metadata[6]['field_label'] = 'Date of birth';
        $metadata[6]['select_choices_or_calculations'] =
        $metadata[6]['field_note'] =
        $metadata[6]['text_validation_type_or_show_slider_number'] = 'date_ymd';
        $metadata[6]['text_validation_min'] =
        $metadata[6]['text_validation_max'] =
        $metadata[6]['identifier'] = 'y';
        $metadata[6]['branching_logic'] =
        $metadata[6]['required_field'] =
        $metadata[6]['custom_alignment'] =
        $metadata[6]['question_number'] =
        $metadata[6]['matrix_group_name'] =
        $metadata[6]['matrix_ranking'] =
        $metadata[6]['field_annotation'] =

        $metadata[7]['field_name'] = 'age';
        $metadata[7]['form_name'] = 'demographics';
        $metadata[7]['section_header'] =
        $metadata[7]['field_type'] = 'calc';
        $metadata[7]['field_label'] = 'Age (years)';
        $metadata[7]['select_choices_or_calculations'] = "rounddown(datediff([dob],'today','y'))";
        $metadata[7]['field_note'] =
        $metadata[7]['text_validation_type_or_show_slider_number'] =
        $metadata[7]['text_validation_min'] =
        $metadata[7]['text_validation_max'] =
        $metadata[7]['identifier'] =
        $metadata[7]['branching_logic'] =
        $metadata[7]['required_field'] =
        $metadata[7]['custom_alignment'] =
        $metadata[7]['question_number'] =
        $metadata[7]['matrix_group_name'] =
        $metadata[7]['matrix_ranking'] =
        $metadata[7]['field_annotation'] =

        $metadata[8]['field_name'] = 'ethnicity';
        $metadata[8]['form_name'] = 'demographics';
        $metadata[8]['section_header'] =
        $metadata[8]['field_type'] = 'radio';
        $metadata[8]['field_label'] = 'Ethnicity';
        $metadata[8]['select_choices_or_calculations'] = '0, Hispanic or Latino | 1, NOT Hispanic or Latino | 2, Unknown / Not Reported';
        $metadata[8]['field_note'] =
        $metadata[8]['text_validation_type_or_show_slider_number'] =
        $metadata[8]['text_validation_min'] =
        $metadata[8]['text_validation_max'] =
        $metadata[8]['identifier'] =
        $metadata[8]['branching_logic'] =
        $metadata[8]['required_field'] =
        $metadata[8]['custom_alignment'] = 'LH';
        $metadata[8]['question_number'] =
        $metadata[8]['matrix_group_name'] =
        $metadata[8]['matrix_ranking'] =
        $metadata[8]['field_annotation'] =

        $metadata[9]['field_name'] = 'race';
        $metadata[9]['form_name'] = 'demographics';
        $metadata[9]['section_header'] =
        $metadata[9]['field_type'] = 'dropdown';
        $metadata[9]['field_label'] = 'Race';
        $metadata[9]['select_choices_or_calculations'] = '0, American Indian/Alaska Native | 1, Asian | 2, Native Hawaiian or Other Pacific Islander | 3, Black or African American | 4, White | 5, More Than One Race | 6, Unknown / Not Reported';
        $metadata[9]['field_note'] =
        $metadata[9]['text_validation_type_or_show_slider_number'] =
        $metadata[9]['text_validation_min'] =
        $metadata[9]['text_validation_max'] =
        $metadata[9]['identifier'] =
        $metadata[9]['branching_logic'] =
        $metadata[9]['required_field'] =
        $metadata[9]['custom_alignment'] =
        $metadata[9]['question_number'] =
        $metadata[9]['matrix_group_name'] =
        $metadata[9]['matrix_ranking'] =
        $metadata[9]['field_annotation'] =

        $metadata[10]['field_name'] = 'sex';
        $metadata[10]['form_name'] = 'demographics';
        $metadata[10]['section_header'] =
        $metadata[10]['field_type'] = 'radio';
        $metadata[10]['field_label'] = 'Sex';
        $metadata[10]['select_choices_or_calculations'] = '0, Female | 1, Male';
        $metadata[10]['field_note'] =
        $metadata[10]['text_validation_type_or_show_slider_number'] =
        $metadata[10]['text_validation_min'] =
        $metadata[10]['text_validation_max'] =
        $metadata[10]['identifier'] =
        $metadata[10]['branching_logic'] =
        $metadata[10]['required_field'] =
        $metadata[10]['custom_alignment'] =
        $metadata[10]['question_number'] =
        $metadata[10]['matrix_group_name'] =
        $metadata[10]['matrix_ranking'] =
        $metadata[10]['field_annotation'] =

        $metadata[11]['field_name'] = 'height';
        $metadata[11]['form_name'] = 'demographics';
        $metadata[11]['section_header'] =
        $metadata[11]['field_type'] = 'text';
        $metadata[11]['field_label'] = 'Height (cm)';
        $metadata[11]['select_choices_or_calculations'] =
        $metadata[11]['field_note'] =
        $metadata[11]['text_validation_type_or_show_slider_number'] = 'number';
        $metadata[11]['text_validation_min'] = 130;
        $metadata[11]['text_validation_max'] = 215;
        $metadata[11]['identifier'] =
        $metadata[11]['branching_logic'] =
        $metadata[11]['required_field'] =
        $metadata[11]['custom_alignment'] =
        $metadata[11]['question_number'] =
        $metadata[11]['matrix_group_name'] =
        $metadata[11]['matrix_ranking'] =
        $metadata[11]['field_annotation'] =

        $metadata[12]['field_name'] = 'weight';
        $metadata[12]['form_name'] = 'demographics';
        $metadata[12]['section_header'] =
        $metadata[12]['field_type'] = 'text';
        $metadata[12]['field_label'] = 'Weight (kilograms)';
        $metadata[12]['select_choices_or_calculations'] =
        $metadata[12]['field_note'] =
        $metadata[12]['text_validation_type_or_show_slider_number'] = 'integer';
        $metadata[12]['text_validation_min'] = 35;
        $metadata[12]['text_validation_max'] = 200;
        $metadata[12]['identifier'] =
        $metadata[12]['branching_logic'] =
        $metadata[12]['required_field'] =
        $metadata[12]['custom_alignment'] =
        $metadata[12]['question_number'] =
        $metadata[12]['matrix_group_name'] =
        $metadata[12]['matrix_ranking'] =
        $metadata[12]['field_annotation'] =

        $metadata[13]['field_name'] = 'bmi';
        $metadata[13]['form_name'] = 'demographics';
        $metadata[13]['section_header'] =
        $metadata[13]['field_type'] = 'calc';
        $metadata[13]['field_label'] = 'BMI';
        $metadata[13]['select_choices_or_calculations'] = 'round(([weight]*10000)/(([height])^(2)),1)';
        $metadata[13]['field_note'] =
        $metadata[13]['text_validation_type_or_show_slider_number'] =
        $metadata[13]['text_validation_min'] =
        $metadata[13]['text_validation_max'] =
        $metadata[13]['identifier'] =
        $metadata[13]['branching_logic'] =
        $metadata[13]['required_field'] =
        $metadata[13]['custom_alignment'] =
        $metadata[13]['question_number'] =
        $metadata[13]['matrix_group_name'] =
        $metadata[13]['matrix_ranking'] =
        $metadata[13]['field_annotation'] =

        $metadata[14]['field_name'] = 'comments';
        $metadata[14]['form_name'] = 'demographics';
        $metadata[14]['section_header'] = 'General Comments';
        $metadata[14]['field_type'] = 'notes';
        $metadata[14]['field_label'] = 'Comments';
        $metadata[14]['select_choices_or_calculations'] =
        $metadata[14]['field_note'] =
        $metadata[14]['text_validation_type_or_show_slider_number'] =
        $metadata[14]['text_validation_min'] =
        $metadata[14]['text_validation_max'] =
        $metadata[14]['identifier'] =
        $metadata[14]['branching_logic'] =
        $metadata[14]['required_field'] =
        $metadata[14]['custom_alignment'] =
        $metadata[14]['question_number'] =
        $metadata[14]['matrix_group_name'] =
        $metadata[14]['matrix_ranking'] =
        $metadata[14]['field_annotation'] =


        $projectXml = '<?xml version="1.0" encoding="UTF-8" ?>
        <ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:redcap="https://projectredcap.org" xsi:schemaLocation="http://www.cdisc.org/ns/odm/v1.3 schema/odm/ODM1-3-1.xsd" ODMVersion="1.3.1" FileOID="000-00-0000" FileType="Snapshot" Description="ETL_Data" AsOfDateTime="2018-11-02T14:57:33" CreationDateTime="2018-11-02T14:57:33" SourceSystem="REDCap" SourceSystemVersion="8.1.10">
        <Study OID="Project.ETLData">
        <GlobalVariables>
                <StudyName>ETL_Data</StudyName>
                <StudyDescription>This file contains the metadata, events, and data for REDCap project "ETL_Data".</StudyDescription>
                <ProtocolName>ETL_Data</ProtocolName>
                <redcap:RecordAutonumberingEnabled>1</redcap:RecordAutonumberingEnabled>
                <redcap:CustomRecordLabel></redcap:CustomRecordLabel>
                <redcap:SecondaryUniqueField></redcap:SecondaryUniqueField>
                <redcap:SchedulingEnabled>0</redcap:SchedulingEnabled>
                <redcap:Purpose>0</redcap:Purpose>
                <redcap:PurposeOther></redcap:PurposeOther>
                <redcap:ProjectNotes></redcap:ProjectNotes>
        </GlobalVariables>
        <MetaDataVersion OID="Metadata.ETLData_2018-11-02_1457" Name="ETL_Data" redcap:RecordIdField="record_id">
                <FormDef OID="Form.demographics" Name="Basic Demography Form" Repeating="No" redcap:FormName="demographics">
                        <ItemGroupRef ItemGroupOID="demographics.record_id" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.first_name" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.last_name" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.comments" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.demographics_complete" Mandatory="No"/>
                </FormDef>
                <ItemGroupDef OID="demographics.record_id" Name="Basic Demography Form" Repeating="No">
                        <ItemRef ItemOID="record_id" Mandatory="No" redcap:Variable="record_id"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demographics.first_name" Name="Contact Information" Repeating="No">
                        <ItemRef ItemOID="first_name" Mandatory="No" redcap:Variable="first_name"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demographics.last_name" Name="Basic Demography Form" Repeating="No">
                        <ItemRef ItemOID="last_name" Mandatory="No" redcap:Variable="last_name"/>
                        <ItemRef ItemOID="address" Mandatory="No" redcap:Variable="address"/>
                        <ItemRef ItemOID="telephone" Mandatory="No" redcap:Variable="telephone"/>
                        <ItemRef ItemOID="email" Mandatory="No" redcap:Variable="email"/>
                        <ItemRef ItemOID="dob" Mandatory="No" redcap:Variable="dob"/>
                        <ItemRef ItemOID="age" Mandatory="No" redcap:Variable="age"/>
                        <ItemRef ItemOID="ethnicity" Mandatory="No" redcap:Variable="ethnicity"/>
                        <ItemRef ItemOID="race" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="sex" Mandatory="No" redcap:Variable="sex"/>
                        <ItemRef ItemOID="height" Mandatory="No" redcap:Variable="height"/>
                        <ItemRef ItemOID="weight" Mandatory="No" redcap:Variable="weight"/>
                        <ItemRef ItemOID="bmi" Mandatory="No" redcap:Variable="bmi"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demographics.comments" Name="General Comments" Repeating="No">
                        <ItemRef ItemOID="comments" Mandatory="No" redcap:Variable="comments"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demographics.demographics_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="demographics_complete" Mandatory="No" redcap:Variable="demographics_complete"/>
                </ItemGroupDef>
                <ItemDef OID="record_id" Name="record_id" DataType="text" Length="999" redcap:Variable="record_id" redcap:FieldType="text">
                        <Question><TranslatedText>Study ID</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="first_name" Name="first_name" DataType="text" Length="999" redcap:Variable="first_name" redcap:FieldType="text" redcap:SectionHeader="Contact Information" redcap:Identifier="y">
                        <Question><TranslatedText>First Name</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="last_name" Name="last_name" DataType="text" Length="999" redcap:Variable="last_name" redcap:FieldType="text" redcap:Identifier="y">
                        <Question><TranslatedText>Last Name</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="address" Name="address" DataType="text" Length="999" redcap:Variable="address" redcap:FieldType="textarea" redcap:Identifier="y">
                        <Question><TranslatedText>Street, City, State, ZIP</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="telephone" Name="telephone" DataType="text" Length="999" redcap:Variable="telephone" redcap:FieldType="text" redcap:TextValidationType="phone" redcap:FieldNote="Include Area Code" redcap:Identifier="y">
                        <Question><TranslatedText>Phone number</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="email" Name="email" DataType="text" Length="999" redcap:Variable="email" redcap:FieldType="text" redcap:TextValidationType="email" redcap:Identifier="y">
                        <Question><TranslatedText>E-mail</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="dob" Name="dob" DataType="date" Length="999" redcap:Variable="dob" redcap:FieldType="text" redcap:TextValidationType="date_ymd" redcap:Identifier="y">
                        <Question><TranslatedText>Date of birth</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="age" Name="age" DataType="float" Length="999" redcap:Variable="age" redcap:FieldType="calc" redcap:Calculation="rounddown(datediff([dob],&#039;today&#039;,&#039;y&#039;))">
                        <Question><TranslatedText>Age (years)</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="ethnicity" Name="ethnicity" DataType="text" Length="1" redcap:Variable="ethnicity" redcap:FieldType="radio" redcap:CustomAlignment="LH">
                        <Question><TranslatedText>Ethnicity</TranslatedText></Question>
                        <CodeListRef CodeListOID="ethnicity.choices"/>
                </ItemDef>
                <ItemDef OID="race" Name="race" DataType="text" Length="1" redcap:Variable="race" redcap:FieldType="select">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race.choices"/>
                </ItemDef>
                <ItemDef OID="sex" Name="sex" DataType="text" Length="1" redcap:Variable="sex" redcap:FieldType="radio">
                        <Question><TranslatedText>Sex</TranslatedText></Question>
                        <CodeListRef CodeListOID="sex.choices"/>
                </ItemDef>
                <ItemDef OID="height" Name="height" DataType="float" Length="999" SignificantDigits="1" redcap:Variable="height" redcap:FieldType="text" redcap:TextValidationType="float">
                        <Question><TranslatedText>Height (cm)</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>130</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (130 - 215). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>215</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (130 - 215). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="weight" Name="weight" DataType="integer" Length="999" redcap:Variable="weight" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Weight (kilograms)</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>35</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (35 - 200). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>200</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (35 - 200). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="bmi" Name="bmi" DataType="float" Length="999" redcap:Variable="bmi" redcap:FieldType="calc" redcap:Calculation="round(([weight]*10000)/(([height])^(2)),1)">
                        <Question><TranslatedText>BMI</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="comments" Name="comments" DataType="text" Length="999" redcap:Variable="comments" redcap:FieldType="textarea" redcap:SectionHeader="General Comments">
                        <Question><TranslatedText>Comments</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="demographics_complete" Name="demographics_complete" DataType="text" Length="1" redcap:Variable="demographics_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="demographics_complete.choices"/>
                </ItemDef>
                <CodeList OID="ethnicity.choices" Name="ethnicity" DataType="text" redcap:Variable="ethnicity">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Hispanic or Latino</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>NOT Hispanic or Latino</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Unknown / Not Reported</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race.choices" Name="race" DataType="text" redcap:Variable="race">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>American Indian/Alaska Native</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Asian</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Native Hawaiian or Other Pacific Islander</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>Black or African American</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="4"><Decode><TranslatedText>White</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="5"><Decode><TranslatedText>More Than One Race</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="6"><Decode><TranslatedText>Unknown / Not Reported</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="sex.choices" Name="sex" DataType="text" redcap:Variable="sex">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Female</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Male</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="demographics_complete.choices" Name="demographics_complete" DataType="text" redcap:Variable="demographics_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
        </MetaDataVersion>
        </Study>
        </ODM>';
    
        $eventMappings = unserialize('a:10:{i:0;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"initial_arm_1";s:4:"form";s:10:"demography";}i:1;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"initial_arm_1";s:4:"form";s:16:"demographyextras";}i:2;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:5:"visit";}i:3;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:11:"visitsurvey";}i:4;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:12:"visitresults";}i:5;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:8:"followup";}i:6;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:5:"visit";}i:7;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:11:"visitsurvey";}i:8;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:12:"visitresults";}i:9;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:8:"followup";}}');
        


        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(['exportProjectInfo', 'exportInstruments', 'exportMetadata', 'exportProjectXml', 'exportInstrumentEventMappings'])
            ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->will($this->returnValue($instruments));

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->will($this->returnValue($metadata));

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->will($this->returnValue($projectXml));

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $string = "TABLE,demographics,demographics_id,ROOT" . "\n"
        . "FIELD,record_id,string" . "\n"
        . "FIELD,first_name,string" . "\n"
        . "FIELD,last_name,string" . "\n"
        . "FIELD,address,string" . "\n"
        . "FIELD,telephone,string" . "\n"
        . "FIELD,email,string" . "\n"
        . "FIELD,dob,date" . "\n"
        . "FIELD,age,string" . "\n"
        . "FIELD,ethnicity,string" . "\n"
        . "FIELD,race,string" . "\n"
        . "FIELD,sex,string" . "\n"
        . "FIELD,height,string" . "\n"
        . "FIELD,weight,string" . "\n"
        . "FIELD,bmi,string" . "\n"
        . "FIELD,comments,string" . "\n" 
        . "\n";

        
        $this->assertSame($rulesText, $string);


 


    }

    public function testGenerateLongitudinal()
    {
        $projectInfo = unserialize('a:23:{s:10:"project_id";s:2:"16";s:13:"project_title";s:17:"REDCap-ETL Visits";s:13:"creation_time";s:19:"2018-05-16 12:37:48";s:15:"production_time";s:0:"";s:13:"in_production";s:1:"0";s:16:"project_language";s:7:"English";s:7:"purpose";s:1:"0";s:13:"purpose_other";s:0:"";s:13:"project_notes";s:0:"";s:19:"custom_record_label";s:0:"";s:22:"secondary_unique_field";s:0:"";s:15:"is_longitudinal";i:1;s:15:"surveys_enabled";s:1:"0";s:18:"scheduling_enabled";s:1:"0";s:28:"record_autonumbering_enabled";s:1:"1";s:21:"randomization_enabled";s:1:"0";s:11:"ddp_enabled";s:1:"0";s:18:"project_irb_number";s:0:"";s:20:"project_grant_number";s:0:"";s:20:"project_pi_firstname";s:0:"";s:19:"project_pi_lastname";s:0:"";s:24:"display_today_now_button";s:1:"1";s:35:"has_repeating_instruments_or_events";i:0;}');
        $instruments = unserialize('a:6:{s:10:"demography";s:10:"Demography";s:16:"demographyextras";s:16:"DemographyExtras";s:5:"visit";s:5:"Visit";s:11:"visitsurvey";s:11:"VisitSurvey";s:12:"visitresults";s:12:"VisitResults";s:8:"followup";s:8:"Followup";}');
        $metadata = unserialize('a:28:{i:0;a:18:{s:10:"field_name";s:9:"record_id";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:9:"Record ID";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:1;a:18:{s:10:"field_name";s:4:"name";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:4:"Name";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:2;a:18:{s:10:"field_name";s:5:"fruit";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:8:"dropdown";s:11:"field_label";s:14:"Favorite fruit";s:30:"select_choices_or_calculations";s:26:"1, apple|2, pear|3, banana";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:12:"autocomplete";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:3;a:18:{s:10:"field_name";s:6:"height";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:6:"Height";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:6:"number";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:4;a:18:{s:10:"field_name";s:6:"weight";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:6:"Weight";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:5;a:18:{s:10:"field_name";s:6:"email1";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:6:"Email1";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:6;a:18:{s:10:"field_name";s:6:"email2";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:6:"Email2";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:7;a:18:{s:10:"field_name";s:7:"echeck1";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:8:"checkbox";s:11:"field_label";s:25:"When do you check email1?";s:30:"select_choices_or_calculations";s:19:"1, Morning|2, Night";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:8;a:18:{s:10:"field_name";s:7:"echeck2";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:8:"checkbox";s:11:"field_label";s:25:"When do you check email2?";s:30:"select_choices_or_calculations";s:19:"1, Morning|2, Night";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:9;a:18:{s:10:"field_name";s:6:"recip1";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:11:"Recipient 1";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:10;a:18:{s:10:"field_name";s:6:"sent1a";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:7:"Sent 1A";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:8:"date_mdy";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:11;a:18:{s:10:"field_name";s:6:"sent1b";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:7:"Sent 1B";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:8:"date_mdy";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:12;a:18:{s:10:"field_name";s:6:"recip2";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:11:"Recipient 2";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:13;a:18:{s:10:"field_name";s:6:"sent2a";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:7:"Sent 2A";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:8:"date_mdy";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:14;a:18:{s:10:"field_name";s:6:"sent2b";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:7:"Sent 2B";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:8:"date_mdy";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:15;a:18:{s:10:"field_name";s:5:"color";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:5:"radio";s:11:"field_label";s:14:"Favorite color";s:30:"select_choices_or_calculations";s:24:"1, red|2, yellow|3, blue";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:16;a:18:{s:10:"field_name";s:5:"rooms";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:8:"checkbox";s:11:"field_label";s:23:"Which rooms do you use?";s:30:"select_choices_or_calculations";s:31:"1, Bedroom|22, Den|303, Kitchen";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:17;a:18:{s:10:"field_name";s:7:"workat1";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:5:"radio";s:11:"field_label";s:34:"For email1, where are you working?";s:30:"select_choices_or_calculations";s:32:"1, Office|2, Home|3, Coffee Shop";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:18;a:18:{s:10:"field_name";s:7:"workat2";s:9:"form_name";s:10:"demography";s:14:"section_header";s:0:"";s:10:"field_type";s:5:"radio";s:11:"field_label";s:34:"For email2, where are you working?";s:30:"select_choices_or_calculations";s:32:"1, Office|2, Home|3, Coffee Shop";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:19;a:18:{s:10:"field_name";s:5:"phone";s:9:"form_name";s:16:"demographyextras";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:5:"Phone";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:20;a:18:{s:10:"field_name";s:3:"dob";s:9:"form_name";s:16:"demographyextras";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:13:"Date of Birth";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:8:"date_mdy";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:21;a:18:{s:10:"field_name";s:10:"visit_date";s:9:"form_name";s:5:"visit";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:10:"Visit Date";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:8:"date_mdy";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:22;a:18:{s:10:"field_name";s:11:"sleep_hours";s:9:"form_name";s:5:"visit";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:11:"Sleep (hrs)";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:10:"number_1dp";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:23;a:18:{s:10:"field_name";s:12:"satisfaction";s:9:"form_name";s:11:"visitsurvey";s:14:"section_header";s:0:"";s:10:"field_type";s:8:"dropdown";s:11:"field_label";s:12:"Satisfaction";s:30:"select_choices_or_calculations";s:24:"1, 1|2, 2|3, 3|4, 4|5, 5";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:12:"autocomplete";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:24;a:18:{s:10:"field_name";s:4:"lab1";s:9:"form_name";s:12:"visitresults";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:5:"Lab 1";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:25;a:18:{s:10:"field_name";s:4:"lab2";s:9:"form_name";s:12:"visitresults";s:14:"section_header";s:0:"";s:10:"field_type";s:4:"text";s:11:"field_label";s:5:"Lab 2";s:30:"select_choices_or_calculations";s:0:"";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:26;a:18:{s:10:"field_name";s:11:"impression1";s:9:"form_name";s:8:"followup";s:14:"section_header";s:0:"";s:10:"field_type";s:5:"radio";s:11:"field_label";s:19:"Followup impression";s:30:"select_choices_or_calculations";s:42:"1, Happy|2, Sad|3, Concerned|4, Interested";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}i:27;a:18:{s:10:"field_name";s:11:"impression2";s:9:"form_name";s:8:"followup";s:14:"section_header";s:0:"";s:10:"field_type";s:5:"radio";s:11:"field_label";s:19:"Followup impression";s:30:"select_choices_or_calculations";s:42:"1, Happy|2, Sad|3, Concerned|4, Interested";s:10:"field_note";s:0:"";s:42:"text_validation_type_or_show_slider_number";s:0:"";s:19:"text_validation_min";s:0:"";s:19:"text_validation_max";s:0:"";s:10:"identifier";s:0:"";s:15:"branching_logic";s:0:"";s:14:"required_field";s:0:"";s:16:"custom_alignment";s:0:"";s:15:"question_number";s:0:"";s:17:"matrix_group_name";s:0:"";s:14:"matrix_ranking";s:0:"";s:16:"field_annotation";s:0:"";}}');

        $projectXml = '<?xml version="1.0" encoding="UTF-8" ?>
        <ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:redcap="https://projectredcap.org" xsi:schemaLocation="http://www.cdisc.org/ns/odm/v1.3 schema/odm/ODM1-3-1.xsd" ODMVersion="1.3.1" FileOID="000-00-0000" FileType="Snapshot" Description="REDCap-ETL Visits" AsOfDateTime="2018-11-05T18:40:14" CreationDateTime="2018-11-05T18:40:14" SourceSystem="REDCap" SourceSystemVersion="8.1.10">
        <Study OID="Project.REDCapETLVisits">
        <GlobalVariables>
                <StudyName>REDCap-ETL Visits</StudyName>
                <StudyDescription>This file contains the metadata, events, and data for REDCap project "REDCap-ETL Visits".</StudyDescription>
                <ProtocolName>REDCap-ETL Visits</ProtocolName>
                <redcap:RecordAutonumberingEnabled>1</redcap:RecordAutonumberingEnabled>
                <redcap:CustomRecordLabel></redcap:CustomRecordLabel>
                <redcap:SecondaryUniqueField></redcap:SecondaryUniqueField>
                <redcap:SchedulingEnabled>0</redcap:SchedulingEnabled>
                <redcap:Purpose>0</redcap:Purpose>
                <redcap:PurposeOther></redcap:PurposeOther>
                <redcap:ProjectNotes></redcap:ProjectNotes>
        </GlobalVariables>
        <MetaDataVersion OID="Metadata.REDCapETLVisits_2018-11-05_1840" Name="REDCap-ETL Visits" redcap:RecordIdField="record_id">
                <Protocol>
                        <StudyEventRef StudyEventOID="Event.initial_arm_1" OrderNumber="1" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.event_1_arm_1" OrderNumber="2" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.event_2_arm_1" OrderNumber="3" Mandatory="No"/>
                </Protocol>
                <StudyEventDef OID="Event.initial_arm_1" Name="Initial" Type="Common" Repeating="No" redcap:EventName="Initial" redcap:CustomEventLabel="" redcap:UniqueEventName="initial_arm_1" redcap:ArmNum="1" redcap:ArmName="Arm 1" redcap:DayOffset="1" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.demography" OrderNumber="1" Mandatory="No" redcap:FormName="demography"/>
                        <FormRef FormOID="Form.demographyextras" OrderNumber="2" Mandatory="No" redcap:FormName="demographyextras"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.event_1_arm_1" Name="Event 1" Type="Common" Repeating="No" redcap:EventName="Event 1" redcap:CustomEventLabel="" redcap:UniqueEventName="event_1_arm_1" redcap:ArmNum="1" redcap:ArmName="Arm 1" redcap:DayOffset="2" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.visit" OrderNumber="1" Mandatory="No" redcap:FormName="visit"/>
                        <FormRef FormOID="Form.visitsurvey" OrderNumber="2" Mandatory="No" redcap:FormName="visitsurvey"/>
                        <FormRef FormOID="Form.visitresults" OrderNumber="3" Mandatory="No" redcap:FormName="visitresults"/>
                        <FormRef FormOID="Form.followup" OrderNumber="4" Mandatory="No" redcap:FormName="followup"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.event_2_arm_1" Name="Event 2" Type="Common" Repeating="No" redcap:EventName="Event 2" redcap:CustomEventLabel="" redcap:UniqueEventName="event_2_arm_1" redcap:ArmNum="1" redcap:ArmName="Arm 1" redcap:DayOffset="3" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.visit" OrderNumber="1" Mandatory="No" redcap:FormName="visit"/>
                        <FormRef FormOID="Form.visitsurvey" OrderNumber="2" Mandatory="No" redcap:FormName="visitsurvey"/>
                        <FormRef FormOID="Form.visitresults" OrderNumber="3" Mandatory="No" redcap:FormName="visitresults"/>
                        <FormRef FormOID="Form.followup" OrderNumber="4" Mandatory="No" redcap:FormName="followup"/>
                </StudyEventDef>
                <FormDef OID="Form.demography" Name="Demography" Repeating="No" redcap:FormName="demography">
                        <ItemGroupRef ItemGroupOID="demography.record_id" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demography.demography_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.demographyextras" Name="DemographyExtras" Repeating="No" redcap:FormName="demographyextras">
                        <ItemGroupRef ItemGroupOID="demographyextras.phone" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographyextras.demographyextras_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.visit" Name="Visit" Repeating="No" redcap:FormName="visit">
                        <ItemGroupRef ItemGroupOID="visit.visit_date" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="visit.visit_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.visitsurvey" Name="VisitSurvey" Repeating="No" redcap:FormName="visitsurvey">
                        <ItemGroupRef ItemGroupOID="visitsurvey.satisfaction" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="visitsurvey.visitsurvey_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.visitresults" Name="VisitResults" Repeating="No" redcap:FormName="visitresults">
                        <ItemGroupRef ItemGroupOID="visitresults.lab1" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="visitresults.visitresults_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.followup" Name="Followup" Repeating="No" redcap:FormName="followup">
                        <ItemGroupRef ItemGroupOID="followup.impression1" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="followup.followup_complete" Mandatory="No"/>
                </FormDef>
                <ItemGroupDef OID="demography.record_id" Name="Demography" Repeating="No">
                        <ItemRef ItemOID="record_id" Mandatory="No" redcap:Variable="record_id"/>
                        <ItemRef ItemOID="name" Mandatory="No" redcap:Variable="name"/>
                        <ItemRef ItemOID="fruit" Mandatory="No" redcap:Variable="fruit"/>
                        <ItemRef ItemOID="height" Mandatory="No" redcap:Variable="height"/>
                        <ItemRef ItemOID="weight" Mandatory="No" redcap:Variable="weight"/>
                        <ItemRef ItemOID="email1" Mandatory="No" redcap:Variable="email1"/>
                        <ItemRef ItemOID="email2" Mandatory="No" redcap:Variable="email2"/>
                        <ItemRef ItemOID="echeck1___1" Mandatory="No" redcap:Variable="echeck1"/>
                        <ItemRef ItemOID="echeck1___2" Mandatory="No" redcap:Variable="echeck1"/>
                        <ItemRef ItemOID="echeck2___1" Mandatory="No" redcap:Variable="echeck2"/>
                        <ItemRef ItemOID="echeck2___2" Mandatory="No" redcap:Variable="echeck2"/>
                        <ItemRef ItemOID="recip1" Mandatory="No" redcap:Variable="recip1"/>
                        <ItemRef ItemOID="sent1a" Mandatory="No" redcap:Variable="sent1a"/>
                        <ItemRef ItemOID="sent1b" Mandatory="No" redcap:Variable="sent1b"/>
                        <ItemRef ItemOID="recip2" Mandatory="No" redcap:Variable="recip2"/>
                        <ItemRef ItemOID="sent2a" Mandatory="No" redcap:Variable="sent2a"/>
                        <ItemRef ItemOID="sent2b" Mandatory="No" redcap:Variable="sent2b"/>
                        <ItemRef ItemOID="color" Mandatory="No" redcap:Variable="color"/>
                        <ItemRef ItemOID="rooms___1" Mandatory="No" redcap:Variable="rooms"/>
                        <ItemRef ItemOID="rooms___22" Mandatory="No" redcap:Variable="rooms"/>
                        <ItemRef ItemOID="rooms___303" Mandatory="No" redcap:Variable="rooms"/>
                        <ItemRef ItemOID="workat1" Mandatory="No" redcap:Variable="workat1"/>
                        <ItemRef ItemOID="workat2" Mandatory="No" redcap:Variable="workat2"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demography.demography_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="demography_complete" Mandatory="No" redcap:Variable="demography_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demographyextras.phone" Name="DemographyExtras" Repeating="No">
                        <ItemRef ItemOID="phone" Mandatory="No" redcap:Variable="phone"/>
                        <ItemRef ItemOID="dob" Mandatory="No" redcap:Variable="dob"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demographyextras.demographyextras_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="demographyextras_complete" Mandatory="No" redcap:Variable="demographyextras_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="visit.visit_date" Name="Visit" Repeating="No">
                        <ItemRef ItemOID="visit_date" Mandatory="No" redcap:Variable="visit_date"/>
                        <ItemRef ItemOID="sleep_hours" Mandatory="No" redcap:Variable="sleep_hours"/>
                </ItemGroupDef>
                <ItemGroupDef OID="visit.visit_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="visit_complete" Mandatory="No" redcap:Variable="visit_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="visitsurvey.satisfaction" Name="VisitSurvey" Repeating="No">
                        <ItemRef ItemOID="satisfaction" Mandatory="No" redcap:Variable="satisfaction"/>
                </ItemGroupDef>
                <ItemGroupDef OID="visitsurvey.visitsurvey_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="visitsurvey_complete" Mandatory="No" redcap:Variable="visitsurvey_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="visitresults.lab1" Name="VisitResults" Repeating="No">
                        <ItemRef ItemOID="lab1" Mandatory="No" redcap:Variable="lab1"/>
                        <ItemRef ItemOID="lab2" Mandatory="No" redcap:Variable="lab2"/>
                </ItemGroupDef>
                <ItemGroupDef OID="visitresults.visitresults_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="visitresults_complete" Mandatory="No" redcap:Variable="visitresults_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="followup.impression1" Name="Followup" Repeating="No">
                        <ItemRef ItemOID="impression1" Mandatory="No" redcap:Variable="impression1"/>
                        <ItemRef ItemOID="impression2" Mandatory="No" redcap:Variable="impression2"/>
                </ItemGroupDef>
                <ItemGroupDef OID="followup.followup_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="followup_complete" Mandatory="No" redcap:Variable="followup_complete"/>
                </ItemGroupDef>
                <ItemDef OID="record_id" Name="record_id" DataType="text" Length="999" redcap:Variable="record_id" redcap:FieldType="text">
                        <Question><TranslatedText>Record ID</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="name" Name="name" DataType="text" Length="999" redcap:Variable="name" redcap:FieldType="text">
                        <Question><TranslatedText>Name</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="fruit" Name="fruit" DataType="text" Length="1" redcap:Variable="fruit" redcap:FieldType="select" redcap:TextValidationType="autocomplete">
                        <Question><TranslatedText>Favorite fruit</TranslatedText></Question>
                        <CodeListRef CodeListOID="fruit.choices"/>
                </ItemDef>
                <ItemDef OID="height" Name="height" DataType="float" Length="999" SignificantDigits="1" redcap:Variable="height" redcap:FieldType="text" redcap:TextValidationType="float">
                        <Question><TranslatedText>Height</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="weight" Name="weight" DataType="text" Length="999" redcap:Variable="weight" redcap:FieldType="text">
                        <Question><TranslatedText>Weight</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="email1" Name="email1" DataType="text" Length="999" redcap:Variable="email1" redcap:FieldType="text">
                        <Question><TranslatedText>Email1</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="email2" Name="email2" DataType="text" Length="999" redcap:Variable="email2" redcap:FieldType="text">
                        <Question><TranslatedText>Email2</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="echeck1___1" Name="echeck1___1" DataType="boolean" Length="1" redcap:Variable="echeck1" redcap:FieldType="checkbox">
                        <Question><TranslatedText>When do you check email1?</TranslatedText></Question>
                        <CodeListRef CodeListOID="echeck1___1.choices"/>
                </ItemDef>
                <ItemDef OID="echeck1___2" Name="echeck1___2" DataType="boolean" Length="1" redcap:Variable="echeck1" redcap:FieldType="checkbox">
                        <Question><TranslatedText>When do you check email1?</TranslatedText></Question>
                        <CodeListRef CodeListOID="echeck1___2.choices"/>
                </ItemDef>
                <ItemDef OID="echeck2___1" Name="echeck2___1" DataType="boolean" Length="1" redcap:Variable="echeck2" redcap:FieldType="checkbox">
                        <Question><TranslatedText>When do you check email2?</TranslatedText></Question>
                        <CodeListRef CodeListOID="echeck2___1.choices"/>
                </ItemDef>
                <ItemDef OID="echeck2___2" Name="echeck2___2" DataType="boolean" Length="1" redcap:Variable="echeck2" redcap:FieldType="checkbox">
                        <Question><TranslatedText>When do you check email2?</TranslatedText></Question>
                        <CodeListRef CodeListOID="echeck2___2.choices"/>
                </ItemDef>
                <ItemDef OID="recip1" Name="recip1" DataType="text" Length="999" redcap:Variable="recip1" redcap:FieldType="text">
                        <Question><TranslatedText>Recipient 1</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="sent1a" Name="sent1a" DataType="date" Length="999" redcap:Variable="sent1a" redcap:FieldType="text" redcap:TextValidationType="date_mdy">
                        <Question><TranslatedText>Sent 1A</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="sent1b" Name="sent1b" DataType="date" Length="999" redcap:Variable="sent1b" redcap:FieldType="text" redcap:TextValidationType="date_mdy">
                        <Question><TranslatedText>Sent 1B</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="recip2" Name="recip2" DataType="text" Length="999" redcap:Variable="recip2" redcap:FieldType="text">
                        <Question><TranslatedText>Recipient 2</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="sent2a" Name="sent2a" DataType="date" Length="999" redcap:Variable="sent2a" redcap:FieldType="text" redcap:TextValidationType="date_mdy">
                        <Question><TranslatedText>Sent 2A</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="sent2b" Name="sent2b" DataType="date" Length="999" redcap:Variable="sent2b" redcap:FieldType="text" redcap:TextValidationType="date_mdy">
                        <Question><TranslatedText>Sent 2B</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="color" Name="color" DataType="text" Length="1" redcap:Variable="color" redcap:FieldType="radio">
                        <Question><TranslatedText>Favorite color</TranslatedText></Question>
                        <CodeListRef CodeListOID="color.choices"/>
                </ItemDef>
                <ItemDef OID="rooms___1" Name="rooms___1" DataType="boolean" Length="1" redcap:Variable="rooms" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Which rooms do you use?</TranslatedText></Question>
                        <CodeListRef CodeListOID="rooms___1.choices"/>
                </ItemDef>
                <ItemDef OID="rooms___22" Name="rooms___22" DataType="boolean" Length="1" redcap:Variable="rooms" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Which rooms do you use?</TranslatedText></Question>
                        <CodeListRef CodeListOID="rooms___22.choices"/>
                </ItemDef>
                <ItemDef OID="rooms___303" Name="rooms___303" DataType="boolean" Length="1" redcap:Variable="rooms" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Which rooms do you use?</TranslatedText></Question>
                        <CodeListRef CodeListOID="rooms___303.choices"/>
                </ItemDef>
                <ItemDef OID="workat1" Name="workat1" DataType="text" Length="1" redcap:Variable="workat1" redcap:FieldType="radio">
                        <Question><TranslatedText>For email1, where are you working?</TranslatedText></Question>
                        <CodeListRef CodeListOID="workat1.choices"/>
                </ItemDef>
                <ItemDef OID="workat2" Name="workat2" DataType="text" Length="1" redcap:Variable="workat2" redcap:FieldType="radio">
                        <Question><TranslatedText>For email2, where are you working?</TranslatedText></Question>
                        <CodeListRef CodeListOID="workat2.choices"/>
                </ItemDef>
                <ItemDef OID="demography_complete" Name="demography_complete" DataType="text" Length="1" redcap:Variable="demography_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="demography_complete.choices"/>
                </ItemDef>
                <ItemDef OID="phone" Name="phone" DataType="text" Length="999" redcap:Variable="phone" redcap:FieldType="text">
                        <Question><TranslatedText>Phone</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="dob" Name="dob" DataType="date" Length="999" redcap:Variable="dob" redcap:FieldType="text" redcap:TextValidationType="date_mdy">
                        <Question><TranslatedText>Date of Birth</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="demographyextras_complete" Name="demographyextras_complete" DataType="text" Length="1" redcap:Variable="demographyextras_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="demographyextras_complete.choices"/>
                </ItemDef>
                <ItemDef OID="visit_date" Name="visit_date" DataType="date" Length="999" redcap:Variable="visit_date" redcap:FieldType="text" redcap:TextValidationType="date_mdy">
                        <Question><TranslatedText>Visit Date</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="sleep_hours" Name="sleep_hours" DataType="float" Length="999" redcap:Variable="sleep_hours" redcap:FieldType="text" redcap:TextValidationType="number_1dp">
                        <Question><TranslatedText>Sleep (hrs)</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="visit_complete" Name="visit_complete" DataType="text" Length="1" redcap:Variable="visit_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="visit_complete.choices"/>
                </ItemDef>
                <ItemDef OID="satisfaction" Name="satisfaction" DataType="text" Length="1" redcap:Variable="satisfaction" redcap:FieldType="select" redcap:TextValidationType="autocomplete">
                        <Question><TranslatedText>Satisfaction</TranslatedText></Question>
                        <CodeListRef CodeListOID="satisfaction.choices"/>
                </ItemDef>
                <ItemDef OID="visitsurvey_complete" Name="visitsurvey_complete" DataType="text" Length="1" redcap:Variable="visitsurvey_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="visitsurvey_complete.choices"/>
                </ItemDef>
                <ItemDef OID="lab1" Name="lab1" DataType="text" Length="999" redcap:Variable="lab1" redcap:FieldType="text">
                        <Question><TranslatedText>Lab 1</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="lab2" Name="lab2" DataType="text" Length="999" redcap:Variable="lab2" redcap:FieldType="text">
                        <Question><TranslatedText>Lab 2</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="visitresults_complete" Name="visitresults_complete" DataType="text" Length="1" redcap:Variable="visitresults_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="visitresults_complete.choices"/>
                </ItemDef>
                <ItemDef OID="impression1" Name="impression1" DataType="text" Length="1" redcap:Variable="impression1" redcap:FieldType="radio">
                        <Question><TranslatedText>Followup impression</TranslatedText></Question>
                        <CodeListRef CodeListOID="impression1.choices"/>
                </ItemDef>
                <ItemDef OID="impression2" Name="impression2" DataType="text" Length="1" redcap:Variable="impression2" redcap:FieldType="radio">
                        <Question><TranslatedText>Followup impression</TranslatedText></Question>
                        <CodeListRef CodeListOID="impression2.choices"/>
                </ItemDef>
                <ItemDef OID="followup_complete" Name="followup_complete" DataType="text" Length="1" redcap:Variable="followup_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="followup_complete.choices"/>
                </ItemDef>
                <CodeList OID="fruit.choices" Name="fruit" DataType="text" redcap:Variable="fruit">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>apple</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>pear</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>banana</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="echeck1___1.choices" Name="echeck1___1" DataType="boolean" redcap:Variable="echeck1" redcap:CheckboxChoices="1, Morning|2, Night">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="echeck1___2.choices" Name="echeck1___2" DataType="boolean" redcap:Variable="echeck1" redcap:CheckboxChoices="1, Morning|2, Night">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="echeck2___1.choices" Name="echeck2___1" DataType="boolean" redcap:Variable="echeck2" redcap:CheckboxChoices="1, Morning|2, Night">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="echeck2___2.choices" Name="echeck2___2" DataType="boolean" redcap:Variable="echeck2" redcap:CheckboxChoices="1, Morning|2, Night">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="color.choices" Name="color" DataType="text" redcap:Variable="color">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>red</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>yellow</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>blue</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="rooms___1.choices" Name="rooms___1" DataType="boolean" redcap:Variable="rooms" redcap:CheckboxChoices="1, Bedroom|22, Den|303, Kitchen">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="rooms___22.choices" Name="rooms___22" DataType="boolean" redcap:Variable="rooms" redcap:CheckboxChoices="1, Bedroom|22, Den|303, Kitchen">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="rooms___303.choices" Name="rooms___303" DataType="boolean" redcap:Variable="rooms" redcap:CheckboxChoices="1, Bedroom|22, Den|303, Kitchen">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="workat1.choices" Name="workat1" DataType="text" redcap:Variable="workat1">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Office</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Home</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>Coffee Shop</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="workat2.choices" Name="workat2" DataType="text" redcap:Variable="workat2">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Office</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Home</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>Coffee Shop</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="demography_complete.choices" Name="demography_complete" DataType="text" redcap:Variable="demography_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="demographyextras_complete.choices" Name="demographyextras_complete" DataType="text" redcap:Variable="demographyextras_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="visit_complete.choices" Name="visit_complete" DataType="text" redcap:Variable="visit_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="satisfaction.choices" Name="satisfaction" DataType="text" redcap:Variable="satisfaction">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>1</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>2</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>3</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="4"><Decode><TranslatedText>4</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="5"><Decode><TranslatedText>5</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="visitsurvey_complete.choices" Name="visitsurvey_complete" DataType="text" redcap:Variable="visitsurvey_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="visitresults_complete.choices" Name="visitresults_complete" DataType="text" redcap:Variable="visitresults_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="impression1.choices" Name="impression1" DataType="text" redcap:Variable="impression1">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Happy</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Sad</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>Concerned</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="4"><Decode><TranslatedText>Interested</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="impression2.choices" Name="impression2" DataType="text" redcap:Variable="impression2">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Happy</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Sad</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>Concerned</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="4"><Decode><TranslatedText>Interested</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="followup_complete.choices" Name="followup_complete" DataType="text" redcap:Variable="followup_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
        </MetaDataVersion>
        </Study>
        </ODM>';
        $eventMappings = unserialize('a:10:{i:0;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"initial_arm_1";s:4:"form";s:10:"demography";}i:1;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"initial_arm_1";s:4:"form";s:16:"demographyextras";}i:2;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:5:"visit";}i:3;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:11:"visitsurvey";}i:4;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:12:"visitresults";}i:5;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_1_arm_1";s:4:"form";s:8:"followup";}i:6;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:5:"visit";}i:7;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:11:"visitsurvey";}i:8;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:12:"visitresults";}i:9;a:3:{s:7:"arm_num";i:1;s:17:"unique_event_name";s:13:"event_2_arm_1";s:4:"form";s:8:"followup";}}');

        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(['exportProjectInfo', 'exportInstruments', 'exportMetadata', 'exportProjectXml', 'exportInstrumentEventMappings'])
            ->getMock();


        // exportProjectInfo() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportProjectInfo')
            ->will($this->returnValue($projectInfo));

        // exportInstruments() - stub method returning mock data
        $dataProject->expects($this->any())
            ->method('exportInstruments')
            ->will($this->returnValue($instruments));

        // exportMetadata() - stub method returning mock data
        $dataProject->expects($this->any())
        ->method('exportMetadata')
        ->will($this->returnValue($metadata));

        // exportProjectXml() - stub method returning mock data

        $dataProject->expects($this->any())
        ->method('exportProjectXml')
        ->will($this->returnValue($projectXml));
    
        $dataProject->expects($this->any())
        ->method('exportInstrumentEventMappings')
        ->will($this->returnValue($eventMappings));

        $rulesGenerator = new RulesGenerator();
        $rulesText = $rulesGenerator->generate($dataProject);

        $result = "TABLE,root,root_id,ROOT" . "\n"
        . "\n"
        . "TABLE,demography,root,EVENTS" . "\n"
        . "FIELD,record_id,string" . "\n"
        . "FIELD,name,string" . "\n"
        . "FIELD,fruit,string" . "\n"
        . "FIELD,height,string" . "\n"
        . "FIELD,weight,string" . "\n"
        . "FIELD,email1,string" . "\n"
        . "FIELD,email2,string" . "\n"
        . "FIELD,echeck1,checkbox" . "\n"
        . "FIELD,echeck2,checkbox" . "\n"
        . "FIELD,recip1,string" . "\n"
        . "FIELD,sent1a,date" . "\n"
        . "FIELD,sent1b,date" . "\n"
        . "FIELD,recip2,string" . "\n"
        . "FIELD,sent2a,date" . "\n"
        . "FIELD,sent2b,date" . "\n"
        . "FIELD,color,string" . "\n"
        . "FIELD,rooms,checkbox" . "\n"
        . "FIELD,workat1,string" . "\n"
        . "FIELD,workat2,string" . "\n"
        . "\n"
        . "TABLE,demographyextras,root,EVENTS" . "\n"
        . "FIELD,phone,string" . "\n"
        . "FIELD,dob,date" . "\n"
        . "\n"
        . "TABLE,visit,root,EVENTS" . "\n"
        . "FIELD,visit_date,date" . "\n"
        . "FIELD,sleep_hours,string" . "\n"
        . "\n"
        . "TABLE,visitsurvey,root,EVENTS" . "\n"
        . "FIELD,satisfaction,string" . "\n"
        . "\n"
        . "TABLE,visitresults,root,EVENTS" . "\n"
        . "FIELD,lab1,string" . "\n"
        . "FIELD,lab2,string" . "\n"
        . "\n"
        . "TABLE,followup,root,EVENTS" . "\n"
        . "FIELD,impression1,string" . "\n"
        . "FIELD,impression2,string" . "\n"
        . "\n";

        $this->assertSame($rulesText, $result);
    }

}