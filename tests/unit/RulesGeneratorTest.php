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
    

        
        $dataProject = $this->getMockBuilder(__NAMESPACE__.'EtlRedCapProject')
            ->setMethods(['exportProjectInfo', 'exportInstruments', 'exportMetadata', 'exportProjectXml'])
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
}