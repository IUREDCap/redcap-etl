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


    public function testBasicGenerate()
    {
        $projectInfo = json_decode('{"project_id":"15","project_title":"REDCap-ETL Basic Demography","creation_time":"2018-05-16 12:34:47","production_time":"","in_production":"0","project_language":"English","purpose":"1","purpose_other":"REDCap ETL testing","project_notes":"","custom_record_label":"","secondary_unique_field":"","is_longitudinal":0,"surveys_enabled":"0","scheduling_enabled":"0","record_autonumbering_enabled":"0","randomization_enabled":"0","ddp_enabled":"0","project_irb_number":"","project_grant_number":"","project_pi_firstname":"","project_pi_lastname":"","display_today_now_button":"1","has_repeating_instruments_or_events":0}', true);

        $instruments = json_decode('{"demographics":"Basic Demography Form"}', true);
       
        $metadata = json_decode('[{"field_name":"record_id","form_name":"demographics","section_header":"","field_type":"text","field_label":"Study ID","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"first_name","form_name":"demographics","section_header":"Contact Information","field_type":"text","field_label":"First Name","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"last_name","form_name":"demographics","section_header":"","field_type":"text","field_label":"Last Name","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"address","form_name":"demographics","section_header":"","field_type":"notes","field_label":"Street, City, State, ZIP","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"telephone","form_name":"demographics","section_header":"","field_type":"text","field_label":"Phone number","select_choices_or_calculations":"","field_note":"Include Area Code","text_validation_type_or_show_slider_number":"phone","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"email","form_name":"demographics","section_header":"","field_type":"text","field_label":"E-mail","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"email","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"dob","form_name":"demographics","section_header":"","field_type":"text","field_label":"Date of birth","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"date_ymd","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"ethnicity","form_name":"demographics","section_header":"","field_type":"radio","field_label":"Ethnicity","select_choices_or_calculations":"0, Hispanic or Latino|1, NOT Hispanic or Latino|2, Unknown \/ Not Reported","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"LH","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"race","form_name":"demographics","section_header":"","field_type":"checkbox","field_label":"Race","select_choices_or_calculations":"0, American Indian\/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"sex","form_name":"demographics","section_header":"","field_type":"radio","field_label":"Sex","select_choices_or_calculations":"0, Female|1, Male","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"height","form_name":"demographics","section_header":"","field_type":"text","field_label":"Height (cm)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"number","text_validation_min":"130","text_validation_max":"215","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"weight","form_name":"demographics","section_header":"","field_type":"text","field_label":"Weight (kilograms)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"35","text_validation_max":"200","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"bmi","form_name":"demographics","section_header":"","field_type":"calc","field_label":"BMI","select_choices_or_calculations":"round(([weight]*10000)\/(([height])^(2)),1)","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"comments","form_name":"demographics","section_header":"General Comments","field_type":"notes","field_label":"Comments","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""}]', true);

        $projectXml = '<?xml version="1.0" encoding="UTF-8" ?>
        <ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:redcap="https://projectredcap.org" xsi:schemaLocation="http://www.cdisc.org/ns/odm/v1.3 schema/odm/ODM1-3-1.xsd" ODMVersion="1.3.1" FileOID="000-00-0000" FileType="Snapshot" Description="REDCap-ETL Basic Demography" AsOfDateTime="2018-11-06T18:30:45" CreationDateTime="2018-11-06T18:30:45" SourceSystem="REDCap" SourceSystemVersion="8.1.10">
        <Study OID="Project.REDCapETLBasicDemography">
        <GlobalVariables>
                <StudyName>REDCap-ETL Basic Demography</StudyName>
                <StudyDescription>This file contains the metadata, events, and data for REDCap project "REDCap-ETL Basic Demography".</StudyDescription>
                <ProtocolName>REDCap-ETL Basic Demography</ProtocolName>
                <redcap:RecordAutonumberingEnabled>0</redcap:RecordAutonumberingEnabled>
                <redcap:CustomRecordLabel></redcap:CustomRecordLabel>
                <redcap:SecondaryUniqueField></redcap:SecondaryUniqueField>
                <redcap:SchedulingEnabled>0</redcap:SchedulingEnabled>
                <redcap:Purpose>1</redcap:Purpose>
                <redcap:PurposeOther>REDCap ETL testing</redcap:PurposeOther>
                <redcap:ProjectNotes></redcap:ProjectNotes>
        </GlobalVariables>
        <MetaDataVersion OID="Metadata.REDCapETLBasicDemography_2018-11-06_1830" Name="REDCap-ETL Basic Demography" redcap:RecordIdField="record_id">
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
                        <ItemRef ItemOID="ethnicity" Mandatory="No" redcap:Variable="ethnicity"/>
                        <ItemRef ItemOID="race___0" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___1" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___2" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___3" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___4" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___5" Mandatory="No" redcap:Variable="race"/>
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
                <ItemDef OID="ethnicity" Name="ethnicity" DataType="text" Length="1" redcap:Variable="ethnicity" redcap:FieldType="radio" redcap:CustomAlignment="LH">
                        <Question><TranslatedText>Ethnicity</TranslatedText></Question>
                        <CodeListRef CodeListOID="ethnicity.choices"/>
                </ItemDef>
                <ItemDef OID="race___0" Name="race___0" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___0.choices"/>
                </ItemDef>
                <ItemDef OID="race___1" Name="race___1" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___1.choices"/>
                </ItemDef>
                <ItemDef OID="race___2" Name="race___2" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___2.choices"/>
                </ItemDef>
                <ItemDef OID="race___3" Name="race___3" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___3.choices"/>
                </ItemDef>
                <ItemDef OID="race___4" Name="race___4" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___4.choices"/>
                </ItemDef>
                <ItemDef OID="race___5" Name="race___5" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___5.choices"/>
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
                <CodeList OID="race___0.choices" Name="race___0" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___1.choices" Name="race___1" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___2.choices" Name="race___2" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___3.choices" Name="race___3" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___4.choices" Name="race___4" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___5.choices" Name="race___5" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
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

        $result = "TABLE,demographics,demographics_id,ROOT" . "\n"
        . "FIELD,record_id,string" . "\n"
        . "FIELD,first_name,string" . "\n"
        . "FIELD,last_name,string" . "\n"
        . "FIELD,address,string" . "\n"
        . "FIELD,telephone,string" . "\n"
        . "FIELD,email,string" . "\n"
        . "FIELD,dob,date" . "\n"
        . "FIELD,ethnicity,string" . "\n"
        . "FIELD,race,checkbox" . "\n"
        . "FIELD,sex,string" . "\n"
        . "FIELD,height,string" . "\n"
        . "FIELD,weight,string" . "\n"
        . "FIELD,bmi,string" . "\n"
        . "FIELD,comments,string" . "\n"
        . "\n";

        $this->assertSame($rulesText, $result);


 


    }

    public function testLongitudinalGenerate()
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
    public function testRepeatingGenerate()
    {
        $projectInfo = json_decode('{"project_id":"20","project_title":"REDCap-ETL Repeating Events","creation_time":"2018-06-06 15:41:03","production_time":"","in_production":"0","project_language":"English","purpose":"0","purpose_other":"","project_notes":"","custom_record_label":"","secondary_unique_field":"","is_longitudinal":1,"surveys_enabled":"0","scheduling_enabled":"0","record_autonumbering_enabled":"1","randomization_enabled":"0","ddp_enabled":"0","project_irb_number":"","project_grant_number":"","project_pi_firstname":"","project_pi_lastname":"","display_today_now_button":"1","has_repeating_instruments_or_events":1}', true);

        $instruments = json_decode('{"enrollment":"Enrollment","contact_information":"Contact Information","emergency_contacts":"Emergency Contacts","weight":"Weight","cardiovascular":"Cardiovascular"}', true);

        $metadata = json_decode('[{"field_name":"record_id","form_name":"enrollment","section_header":"","field_type":"text","field_label":"Record ID","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"registration_date","form_name":"enrollment","section_header":"","field_type":"text","field_label":"Registration date","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"date_mdy","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"y","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":"@DEFAULT=TODAY"},{"field_name":"first_name","form_name":"enrollment","section_header":"","field_type":"text","field_label":"First name","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"y","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"last_name","form_name":"enrollment","section_header":"","field_type":"text","field_label":"Last name","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"y","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"birthdate","form_name":"enrollment","section_header":"","field_type":"text","field_label":"Birthdate","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"date_mdy","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"y","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"registration_age","form_name":"enrollment","section_header":"","field_type":"calc","field_label":"Age at registration","select_choices_or_calculations":"rounddown(datediff([registration_date],[birthdate],\'y\',\'mdy\'))","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"gender","form_name":"enrollment","section_header":"","field_type":"radio","field_label":"Gender","select_choices_or_calculations":"0, male|1, female","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"y","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"race","form_name":"enrollment","section_header":"","field_type":"checkbox","field_label":"Race","select_choices_or_calculations":"0, American Indian\/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"home_address","form_name":"contact_information","section_header":"","field_type":"text","field_label":"Home address","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"y","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"phone1","form_name":"contact_information","section_header":"","field_type":"text","field_label":"Phone 1","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"phone","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"y","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"phone_type1","form_name":"contact_information","section_header":"","field_type":"dropdown","field_label":"Phone Type 1","select_choices_or_calculations":"0, cell|1, home|2, work|3, other","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"phone2","form_name":"contact_information","section_header":"","field_type":"text","field_label":"Phone 2","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"phone","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"phone_type2","form_name":"contact_information","section_header":"","field_type":"dropdown","field_label":"Phone Type 2","select_choices_or_calculations":"0, cell|1, home|2, work|3, other","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"phone3","form_name":"contact_information","section_header":"","field_type":"text","field_label":"Phone 3","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"phone","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"phone_type3","form_name":"contact_information","section_header":"","field_type":"dropdown","field_label":"Phone Type 3","select_choices_or_calculations":"0, cell|1, home|2, work|3, other","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"email","form_name":"contact_information","section_header":"","field_type":"text","field_label":"E-mail","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"email","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"em_contact1","form_name":"emergency_contacts","section_header":"","field_type":"text","field_label":"Emergency contact 1","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"em_contact_phone1a","form_name":"emergency_contacts","section_header":"","field_type":"text","field_label":"Emergency contact 1 phone a","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"em_contact_phone1b","form_name":"emergency_contacts","section_header":"","field_type":"text","field_label":"Emergency contact 1 phone b","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"em_contact2","form_name":"emergency_contacts","section_header":"","field_type":"text","field_label":"Emergency contact 2","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"em_contact_phone2a","form_name":"emergency_contacts","section_header":"","field_type":"text","field_label":"Emergency contact 2 phone a","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"em_contact_phone2b","form_name":"emergency_contacts","section_header":"","field_type":"text","field_label":"Emergency contact 2 phone b","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"weight_time","form_name":"weight","section_header":"","field_type":"text","field_label":"Weight time","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"datetime_mdy","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"weight_kg","form_name":"weight","section_header":"","field_type":"text","field_label":"Weight (kg)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"number_1dp","text_validation_min":"0","text_validation_max":"500","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"height_m","form_name":"weight","section_header":"","field_type":"text","field_label":"Height (m)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"number_2dp","text_validation_min":"0.00","text_validation_max":"3.00","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"cardiovascular_date","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Cardiovascular date","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"date_mdy","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"hdl_mg_dl","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"HDL (mg\/dL)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"ldl_mg_dl","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"LDL (mg\/dL)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"0","text_validation_max":"1000","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"triglycerides_mg_dl","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Triglycerides (mg\/dL)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"diastolic1","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Blood pressure - diastolic 1","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"diastolic2","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Blood pressure - diastolic 2","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"diastolic3","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Blood pressure - diastolic 3","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"systolic1","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Blood pressure - systolic 1","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"systolic2","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Blood pressure - systolic 2","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"systolic3","form_name":"cardiovascular","section_header":"","field_type":"text","field_label":"Blood pressure - systolic 3","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""}]', true);

        $projectXml = '<?xml version="1.0" encoding="UTF-8" ?>
        <ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:redcap="https://projectredcap.org" xsi:schemaLocation="http://www.cdisc.org/ns/odm/v1.3 schema/odm/ODM1-3-1.xsd" ODMVersion="1.3.1" FileOID="000-00-0000" FileType="Snapshot" Description="REDCap-ETL Repeating Events" AsOfDateTime="2018-11-06T19:50:07" CreationDateTime="2018-11-06T19:50:07" SourceSystem="REDCap" SourceSystemVersion="8.1.10">
        <Study OID="Project.REDCapETLRepeatingEvents">
        <GlobalVariables>
                <StudyName>REDCap-ETL Repeating Events</StudyName>
                <StudyDescription>This file contains the metadata, events, and data for REDCap project "REDCap-ETL Repeating Events".</StudyDescription>
                <ProtocolName>REDCap-ETL Repeating Events</ProtocolName>
                <redcap:RecordAutonumberingEnabled>1</redcap:RecordAutonumberingEnabled>
                <redcap:CustomRecordLabel></redcap:CustomRecordLabel>
                <redcap:SecondaryUniqueField></redcap:SecondaryUniqueField>
                <redcap:SchedulingEnabled>0</redcap:SchedulingEnabled>
                <redcap:Purpose>0</redcap:Purpose>
                <redcap:PurposeOther></redcap:PurposeOther>
                <redcap:ProjectNotes></redcap:ProjectNotes>
                <redcap:RepeatingInstrumentsAndEvents>
                        <redcap:RepeatingEvent redcap:UniqueEventName="visit_arm_1"/>
                        <redcap:RepeatingEvent redcap:UniqueEventName="visit_arm_2"/>
                        <redcap:RepeatingEvent redcap:UniqueEventName="visit_arm_3"/>
                        <redcap:RepeatingInstruments>
                                <redcap:RepeatingInstrument redcap:UniqueEventName="home_visit_arm_1" redcap:RepeatInstrument="weight" redcap:CustomLabel=""/>
                                <redcap:RepeatingInstrument redcap:UniqueEventName="home_visit_arm_1" redcap:RepeatInstrument="cardiovascular" redcap:CustomLabel=""/>
                        </redcap:RepeatingInstruments>
                        <redcap:RepeatingInstruments>
                                <redcap:RepeatingInstrument redcap:UniqueEventName="home_visit_arm_2" redcap:RepeatInstrument="weight" redcap:CustomLabel=""/>
                                <redcap:RepeatingInstrument redcap:UniqueEventName="home_visit_arm_2" redcap:RepeatInstrument="cardiovascular" redcap:CustomLabel=""/>
                        </redcap:RepeatingInstruments>
                        <redcap:RepeatingInstruments>
                                <redcap:RepeatingInstrument redcap:UniqueEventName="home_visit_arm_3" redcap:RepeatInstrument="weight" redcap:CustomLabel=""/>
                                <redcap:RepeatingInstrument redcap:UniqueEventName="home_visit_arm_3" redcap:RepeatInstrument="cardiovascular" redcap:CustomLabel=""/>
                        </redcap:RepeatingInstruments>
                </redcap:RepeatingInstrumentsAndEvents>
        </GlobalVariables>
        <MetaDataVersion OID="Metadata.REDCapETLRepeatingEvents_2018-11-06_1950" Name="REDCap-ETL Repeating Events" redcap:RecordIdField="record_id">
                <Protocol>
                        <StudyEventRef StudyEventOID="Event.enrollment_arm_1" OrderNumber="1" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.baseline_arm_1" OrderNumber="2" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.visit_arm_1" OrderNumber="3" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.home_visit_arm_1" OrderNumber="4" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.enrollment_arm_2" OrderNumber="5" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.baseline_arm_2" OrderNumber="6" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.visit_arm_2" OrderNumber="7" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.home_visit_arm_2" OrderNumber="8" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.enrollment_arm_3" OrderNumber="9" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.baseline_arm_3" OrderNumber="10" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.visit_arm_3" OrderNumber="11" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.home_visit_arm_3" OrderNumber="12" Mandatory="No"/>
                </Protocol>
                <StudyEventDef OID="Event.enrollment_arm_1" Name="Enrollment (Arm 1: control)" Type="Common" Repeating="No" redcap:EventName="Enrollment" redcap:CustomEventLabel="" redcap:UniqueEventName="enrollment_arm_1" redcap:ArmNum="1" redcap:ArmName="control" redcap:DayOffset="1" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.enrollment" OrderNumber="1" Mandatory="No" redcap:FormName="enrollment"/>
                        <FormRef FormOID="Form.contact_information" OrderNumber="2" Mandatory="No" redcap:FormName="contact_information"/>
                        <FormRef FormOID="Form.emergency_contacts" OrderNumber="3" Mandatory="No" redcap:FormName="emergency_contacts"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.baseline_arm_1" Name="Baseline (Arm 1: control)" Type="Common" Repeating="No" redcap:EventName="Baseline" redcap:CustomEventLabel="" redcap:UniqueEventName="baseline_arm_1" redcap:ArmNum="1" redcap:ArmName="control" redcap:DayOffset="2" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.visit_arm_1" Name="Visit (Arm 1: control)" Type="Common" Repeating="No" redcap:EventName="Visit" redcap:CustomEventLabel="" redcap:UniqueEventName="visit_arm_1" redcap:ArmNum="1" redcap:ArmName="control" redcap:DayOffset="3" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.home_visit_arm_1" Name="Home Visit (Arm 1: control)" Type="Common" Repeating="No" redcap:EventName="Home Visit" redcap:CustomEventLabel="" redcap:UniqueEventName="home_visit_arm_1" redcap:ArmNum="1" redcap:ArmName="control" redcap:DayOffset="4" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.enrollment_arm_2" Name="Enrollment (Arm 2: wfpb)" Type="Common" Repeating="No" redcap:EventName="Enrollment" redcap:CustomEventLabel="" redcap:UniqueEventName="enrollment_arm_2" redcap:ArmNum="2" redcap:ArmName="wfpb" redcap:DayOffset="1" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.enrollment" OrderNumber="1" Mandatory="No" redcap:FormName="enrollment"/>
                        <FormRef FormOID="Form.contact_information" OrderNumber="2" Mandatory="No" redcap:FormName="contact_information"/>
                        <FormRef FormOID="Form.emergency_contacts" OrderNumber="3" Mandatory="No" redcap:FormName="emergency_contacts"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.baseline_arm_2" Name="Baseline (Arm 2: wfpb)" Type="Common" Repeating="No" redcap:EventName="Baseline" redcap:CustomEventLabel="" redcap:UniqueEventName="baseline_arm_2" redcap:ArmNum="2" redcap:ArmName="wfpb" redcap:DayOffset="2" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.visit_arm_2" Name="Visit (Arm 2: wfpb)" Type="Common" Repeating="No" redcap:EventName="Visit" redcap:CustomEventLabel="" redcap:UniqueEventName="visit_arm_2" redcap:ArmNum="2" redcap:ArmName="wfpb" redcap:DayOffset="3" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.home_visit_arm_2" Name="Home Visit (Arm 2: wfpb)" Type="Common" Repeating="No" redcap:EventName="Home Visit" redcap:CustomEventLabel="" redcap:UniqueEventName="home_visit_arm_2" redcap:ArmNum="2" redcap:ArmName="wfpb" redcap:DayOffset="4" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.enrollment_arm_3" Name="Enrollment (Arm 3: lchf)" Type="Common" Repeating="No" redcap:EventName="Enrollment" redcap:CustomEventLabel="" redcap:UniqueEventName="enrollment_arm_3" redcap:ArmNum="3" redcap:ArmName="lchf" redcap:DayOffset="1" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.enrollment" OrderNumber="1" Mandatory="No" redcap:FormName="enrollment"/>
                        <FormRef FormOID="Form.contact_information" OrderNumber="2" Mandatory="No" redcap:FormName="contact_information"/>
                        <FormRef FormOID="Form.emergency_contacts" OrderNumber="3" Mandatory="No" redcap:FormName="emergency_contacts"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.baseline_arm_3" Name="Baseline (Arm 3: lchf)" Type="Common" Repeating="No" redcap:EventName="Baseline" redcap:CustomEventLabel="" redcap:UniqueEventName="baseline_arm_3" redcap:ArmNum="3" redcap:ArmName="lchf" redcap:DayOffset="2" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.visit_arm_3" Name="Visit (Arm 3: lchf)" Type="Common" Repeating="No" redcap:EventName="Visit" redcap:CustomEventLabel="" redcap:UniqueEventName="visit_arm_3" redcap:ArmNum="3" redcap:ArmName="lchf" redcap:DayOffset="3" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.home_visit_arm_3" Name="Home Visit (Arm 3: lchf)" Type="Common" Repeating="No" redcap:EventName="Home Visit" redcap:CustomEventLabel="" redcap:UniqueEventName="home_visit_arm_3" redcap:ArmNum="3" redcap:ArmName="lchf" redcap:DayOffset="4" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.weight" OrderNumber="1" Mandatory="No" redcap:FormName="weight"/>
                        <FormRef FormOID="Form.cardiovascular" OrderNumber="2" Mandatory="No" redcap:FormName="cardiovascular"/>
                </StudyEventDef>
                <FormDef OID="Form.enrollment" Name="Enrollment" Repeating="No" redcap:FormName="enrollment">
                        <ItemGroupRef ItemGroupOID="enrollment.record_id" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="enrollment.enrollment_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.contact_information" Name="Contact Information" Repeating="No" redcap:FormName="contact_information">
                        <ItemGroupRef ItemGroupOID="contact_information.home_address" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="contact_information.contact_information_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.emergency_contacts" Name="Emergency Contacts" Repeating="No" redcap:FormName="emergency_contacts">
                        <ItemGroupRef ItemGroupOID="emergency_contacts.em_contact1" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="emergency_contacts.emergency_contacts_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.weight" Name="Weight" Repeating="No" redcap:FormName="weight">
                        <ItemGroupRef ItemGroupOID="weight.weight_time" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="weight.weight_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.cardiovascular" Name="Cardiovascular" Repeating="No" redcap:FormName="cardiovascular">
                        <ItemGroupRef ItemGroupOID="cardiovascular.cardiovascular_date" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="cardiovascular.cardiovascular_complete" Mandatory="No"/>
                </FormDef>
                <ItemGroupDef OID="enrollment.record_id" Name="Enrollment" Repeating="No">
                        <ItemRef ItemOID="record_id" Mandatory="No" redcap:Variable="record_id"/>
                        <ItemRef ItemOID="registration_date" Mandatory="Yes" redcap:Variable="registration_date"/>
                        <ItemRef ItemOID="first_name" Mandatory="Yes" redcap:Variable="first_name"/>
                        <ItemRef ItemOID="last_name" Mandatory="Yes" redcap:Variable="last_name"/>
                        <ItemRef ItemOID="birthdate" Mandatory="Yes" redcap:Variable="birthdate"/>
                        <ItemRef ItemOID="registration_age" Mandatory="No" redcap:Variable="registration_age"/>
                        <ItemRef ItemOID="gender" Mandatory="Yes" redcap:Variable="gender"/>
                        <ItemRef ItemOID="race___0" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___1" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___2" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___3" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___4" Mandatory="No" redcap:Variable="race"/>
                        <ItemRef ItemOID="race___5" Mandatory="No" redcap:Variable="race"/>
                </ItemGroupDef>
                <ItemGroupDef OID="enrollment.enrollment_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="enrollment_complete" Mandatory="No" redcap:Variable="enrollment_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="contact_information.home_address" Name="Contact Information" Repeating="No">
                        <ItemRef ItemOID="home_address" Mandatory="Yes" redcap:Variable="home_address"/>
                        <ItemRef ItemOID="phone1" Mandatory="Yes" redcap:Variable="phone1"/>
                        <ItemRef ItemOID="phone_type1" Mandatory="No" redcap:Variable="phone_type1"/>
                        <ItemRef ItemOID="phone2" Mandatory="No" redcap:Variable="phone2"/>
                        <ItemRef ItemOID="phone_type2" Mandatory="No" redcap:Variable="phone_type2"/>
                        <ItemRef ItemOID="phone3" Mandatory="No" redcap:Variable="phone3"/>
                        <ItemRef ItemOID="phone_type3" Mandatory="No" redcap:Variable="phone_type3"/>
                        <ItemRef ItemOID="email" Mandatory="No" redcap:Variable="email"/>
                </ItemGroupDef>
                <ItemGroupDef OID="contact_information.contact_information_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="contact_information_complete" Mandatory="No" redcap:Variable="contact_information_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="emergency_contacts.em_contact1" Name="Emergency Contacts" Repeating="No">
                        <ItemRef ItemOID="em_contact1" Mandatory="No" redcap:Variable="em_contact1"/>
                        <ItemRef ItemOID="em_contact_phone1a" Mandatory="No" redcap:Variable="em_contact_phone1a"/>
                        <ItemRef ItemOID="em_contact_phone1b" Mandatory="No" redcap:Variable="em_contact_phone1b"/>
                        <ItemRef ItemOID="em_contact2" Mandatory="No" redcap:Variable="em_contact2"/>
                        <ItemRef ItemOID="em_contact_phone2a" Mandatory="No" redcap:Variable="em_contact_phone2a"/>
                        <ItemRef ItemOID="em_contact_phone2b" Mandatory="No" redcap:Variable="em_contact_phone2b"/>
                </ItemGroupDef>
                <ItemGroupDef OID="emergency_contacts.emergency_contacts_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="emergency_contacts_complete" Mandatory="No" redcap:Variable="emergency_contacts_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="weight.weight_time" Name="Weight" Repeating="No">
                        <ItemRef ItemOID="weight_time" Mandatory="No" redcap:Variable="weight_time"/>
                        <ItemRef ItemOID="weight_kg" Mandatory="No" redcap:Variable="weight_kg"/>
                        <ItemRef ItemOID="height_m" Mandatory="No" redcap:Variable="height_m"/>
                </ItemGroupDef>
                <ItemGroupDef OID="weight.weight_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="weight_complete" Mandatory="No" redcap:Variable="weight_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="cardiovascular.cardiovascular_date" Name="Cardiovascular" Repeating="No">
                        <ItemRef ItemOID="cardiovascular_date" Mandatory="No" redcap:Variable="cardiovascular_date"/>
                        <ItemRef ItemOID="hdl_mg_dl" Mandatory="No" redcap:Variable="hdl_mg_dl"/>
                        <ItemRef ItemOID="ldl_mg_dl" Mandatory="No" redcap:Variable="ldl_mg_dl"/>
                        <ItemRef ItemOID="triglycerides_mg_dl" Mandatory="No" redcap:Variable="triglycerides_mg_dl"/>
                        <ItemRef ItemOID="diastolic1" Mandatory="No" redcap:Variable="diastolic1"/>
                        <ItemRef ItemOID="diastolic2" Mandatory="No" redcap:Variable="diastolic2"/>
                        <ItemRef ItemOID="diastolic3" Mandatory="No" redcap:Variable="diastolic3"/>
                        <ItemRef ItemOID="systolic1" Mandatory="No" redcap:Variable="systolic1"/>
                        <ItemRef ItemOID="systolic2" Mandatory="No" redcap:Variable="systolic2"/>
                        <ItemRef ItemOID="systolic3" Mandatory="No" redcap:Variable="systolic3"/>
                </ItemGroupDef>
                <ItemGroupDef OID="cardiovascular.cardiovascular_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="cardiovascular_complete" Mandatory="No" redcap:Variable="cardiovascular_complete"/>
                </ItemGroupDef>
                <ItemDef OID="record_id" Name="record_id" DataType="text" Length="999" redcap:Variable="record_id" redcap:FieldType="text">
                        <Question><TranslatedText>Record ID</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="registration_date" Name="registration_date" DataType="date" Length="999" redcap:Variable="registration_date" redcap:FieldType="text" redcap:TextValidationType="date_mdy" redcap:RequiredField="y" redcap:FieldAnnotation="@DEFAULT=TODAY">
                        <Question><TranslatedText>Registration date</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="first_name" Name="first_name" DataType="text" Length="999" redcap:Variable="first_name" redcap:FieldType="text" redcap:Identifier="y" redcap:RequiredField="y">
                        <Question><TranslatedText>First name</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="last_name" Name="last_name" DataType="text" Length="999" redcap:Variable="last_name" redcap:FieldType="text" redcap:Identifier="y" redcap:RequiredField="y">
                        <Question><TranslatedText>Last name</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="birthdate" Name="birthdate" DataType="date" Length="999" redcap:Variable="birthdate" redcap:FieldType="text" redcap:TextValidationType="date_mdy" redcap:Identifier="y" redcap:RequiredField="y">
                        <Question><TranslatedText>Birthdate</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="registration_age" Name="registration_age" DataType="float" Length="999" redcap:Variable="registration_age" redcap:FieldType="calc" redcap:Calculation="rounddown(datediff([registration_date],[birthdate],&#039;y&#039;,&#039;mdy&#039;))">
                        <Question><TranslatedText>Age at registration</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="gender" Name="gender" DataType="text" Length="1" redcap:Variable="gender" redcap:FieldType="radio" redcap:RequiredField="y">
                        <Question><TranslatedText>Gender</TranslatedText></Question>
                        <CodeListRef CodeListOID="gender.choices"/>
                </ItemDef>
                <ItemDef OID="race___0" Name="race___0" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___0.choices"/>
                </ItemDef>
                <ItemDef OID="race___1" Name="race___1" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___1.choices"/>
                </ItemDef>
                <ItemDef OID="race___2" Name="race___2" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___2.choices"/>
                </ItemDef>
                <ItemDef OID="race___3" Name="race___3" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___3.choices"/>
                </ItemDef>
                <ItemDef OID="race___4" Name="race___4" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___4.choices"/>
                </ItemDef>
                <ItemDef OID="race___5" Name="race___5" DataType="boolean" Length="1" redcap:Variable="race" redcap:FieldType="checkbox">
                        <Question><TranslatedText>Race</TranslatedText></Question>
                        <CodeListRef CodeListOID="race___5.choices"/>
                </ItemDef>
                <ItemDef OID="enrollment_complete" Name="enrollment_complete" DataType="text" Length="1" redcap:Variable="enrollment_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="enrollment_complete.choices"/>
                </ItemDef>
                <ItemDef OID="home_address" Name="home_address" DataType="text" Length="999" redcap:Variable="home_address" redcap:FieldType="text" redcap:RequiredField="y">
                        <Question><TranslatedText>Home address</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="phone1" Name="phone1" DataType="text" Length="999" redcap:Variable="phone1" redcap:FieldType="text" redcap:TextValidationType="phone" redcap:RequiredField="y">
                        <Question><TranslatedText>Phone 1</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="phone_type1" Name="phone_type1" DataType="text" Length="1" redcap:Variable="phone_type1" redcap:FieldType="select">
                        <Question><TranslatedText>Phone Type 1</TranslatedText></Question>
                        <CodeListRef CodeListOID="phone_type1.choices"/>
                </ItemDef>
                <ItemDef OID="phone2" Name="phone2" DataType="text" Length="999" redcap:Variable="phone2" redcap:FieldType="text" redcap:TextValidationType="phone">
                        <Question><TranslatedText>Phone 2</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="phone_type2" Name="phone_type2" DataType="text" Length="1" redcap:Variable="phone_type2" redcap:FieldType="select">
                        <Question><TranslatedText>Phone Type 2</TranslatedText></Question>
                        <CodeListRef CodeListOID="phone_type2.choices"/>
                </ItemDef>
                <ItemDef OID="phone3" Name="phone3" DataType="text" Length="999" redcap:Variable="phone3" redcap:FieldType="text" redcap:TextValidationType="phone">
                        <Question><TranslatedText>Phone 3</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="phone_type3" Name="phone_type3" DataType="text" Length="1" redcap:Variable="phone_type3" redcap:FieldType="select">
                        <Question><TranslatedText>Phone Type 3</TranslatedText></Question>
                        <CodeListRef CodeListOID="phone_type3.choices"/>
                </ItemDef>
                <ItemDef OID="email" Name="email" DataType="text" Length="999" redcap:Variable="email" redcap:FieldType="text" redcap:TextValidationType="email">
                        <Question><TranslatedText>E-mail</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="contact_information_complete" Name="contact_information_complete" DataType="text" Length="1" redcap:Variable="contact_information_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="contact_information_complete.choices"/>
                </ItemDef>
                <ItemDef OID="em_contact1" Name="em_contact1" DataType="text" Length="999" redcap:Variable="em_contact1" redcap:FieldType="text">
                        <Question><TranslatedText>Emergency contact 1</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="em_contact_phone1a" Name="em_contact_phone1a" DataType="text" Length="999" redcap:Variable="em_contact_phone1a" redcap:FieldType="text">
                        <Question><TranslatedText>Emergency contact 1 phone a</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="em_contact_phone1b" Name="em_contact_phone1b" DataType="text" Length="999" redcap:Variable="em_contact_phone1b" redcap:FieldType="text">
                        <Question><TranslatedText>Emergency contact 1 phone b</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="em_contact2" Name="em_contact2" DataType="text" Length="999" redcap:Variable="em_contact2" redcap:FieldType="text">
                        <Question><TranslatedText>Emergency contact 2</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="em_contact_phone2a" Name="em_contact_phone2a" DataType="text" Length="999" redcap:Variable="em_contact_phone2a" redcap:FieldType="text">
                        <Question><TranslatedText>Emergency contact 2 phone a</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="em_contact_phone2b" Name="em_contact_phone2b" DataType="text" Length="999" redcap:Variable="em_contact_phone2b" redcap:FieldType="text">
                        <Question><TranslatedText>Emergency contact 2 phone b</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="emergency_contacts_complete" Name="emergency_contacts_complete" DataType="text" Length="1" redcap:Variable="emergency_contacts_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="emergency_contacts_complete.choices"/>
                </ItemDef>
                <ItemDef OID="weight_time" Name="weight_time" DataType="partialDatetime" Length="999" redcap:Variable="weight_time" redcap:FieldType="text" redcap:TextValidationType="datetime_mdy">
                        <Question><TranslatedText>Weight time</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="weight_kg" Name="weight_kg" DataType="float" Length="999" redcap:Variable="weight_kg" redcap:FieldType="text" redcap:TextValidationType="number_1dp">
                        <Question><TranslatedText>Weight (kg)</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>0</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (0 - 500). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>500</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (0 - 500). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="height_m" Name="height_m" DataType="float" Length="999" redcap:Variable="height_m" redcap:FieldType="text" redcap:TextValidationType="number_2dp">
                        <Question><TranslatedText>Height (m)</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>0.00</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (0.00 - 3.00). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>3.00</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (0.00 - 3.00). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="weight_complete" Name="weight_complete" DataType="text" Length="1" redcap:Variable="weight_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="weight_complete.choices"/>
                </ItemDef>
                <ItemDef OID="cardiovascular_date" Name="cardiovascular_date" DataType="date" Length="999" redcap:Variable="cardiovascular_date" redcap:FieldType="text" redcap:TextValidationType="date_mdy">
                        <Question><TranslatedText>Cardiovascular date</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="hdl_mg_dl" Name="hdl_mg_dl" DataType="integer" Length="999" redcap:Variable="hdl_mg_dl" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>HDL (mg/dL)</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="ldl_mg_dl" Name="ldl_mg_dl" DataType="integer" Length="999" redcap:Variable="ldl_mg_dl" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>LDL (mg/dL)</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>0</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (0 - 1000). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>1000</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (0 - 1000). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="triglycerides_mg_dl" Name="triglycerides_mg_dl" DataType="integer" Length="999" redcap:Variable="triglycerides_mg_dl" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Triglycerides (mg/dL)</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="diastolic1" Name="diastolic1" DataType="integer" Length="999" redcap:Variable="diastolic1" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Blood pressure - diastolic 1</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="diastolic2" Name="diastolic2" DataType="integer" Length="999" redcap:Variable="diastolic2" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Blood pressure - diastolic 2</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="diastolic3" Name="diastolic3" DataType="integer" Length="999" redcap:Variable="diastolic3" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Blood pressure - diastolic 3</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="systolic1" Name="systolic1" DataType="integer" Length="999" redcap:Variable="systolic1" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Blood pressure - systolic 1</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="systolic2" Name="systolic2" DataType="integer" Length="999" redcap:Variable="systolic2" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Blood pressure - systolic 2</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="systolic3" Name="systolic3" DataType="integer" Length="999" redcap:Variable="systolic3" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Blood pressure - systolic 3</TranslatedText></Question>
                </ItemDef>
                <ItemDef OID="cardiovascular_complete" Name="cardiovascular_complete" DataType="text" Length="1" redcap:Variable="cardiovascular_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="cardiovascular_complete.choices"/>
                </ItemDef>
                <CodeList OID="gender.choices" Name="gender" DataType="text" redcap:Variable="gender">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>male</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>female</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___0.choices" Name="race___0" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___1.choices" Name="race___1" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___2.choices" Name="race___2" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___3.choices" Name="race___3" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___4.choices" Name="race___4" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="race___5.choices" Name="race___5" DataType="boolean" redcap:Variable="race" redcap:CheckboxChoices="0, American Indian/Alaska Native|1, Asian|2, Native Hawaiian or Other Pacific Islander|3, Black or African American|4, White|5, Other">
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Checked</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Unchecked</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="enrollment_complete.choices" Name="enrollment_complete" DataType="text" redcap:Variable="enrollment_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="phone_type1.choices" Name="phone_type1" DataType="text" redcap:Variable="phone_type1">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>cell</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>home</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>work</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>other</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="phone_type2.choices" Name="phone_type2" DataType="text" redcap:Variable="phone_type2">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>cell</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>home</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>work</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>other</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="phone_type3.choices" Name="phone_type3" DataType="text" redcap:Variable="phone_type3">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>cell</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>home</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>work</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="3"><Decode><TranslatedText>other</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="contact_information_complete.choices" Name="contact_information_complete" DataType="text" redcap:Variable="contact_information_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="emergency_contacts_complete.choices" Name="emergency_contacts_complete" DataType="text" redcap:Variable="emergency_contacts_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="weight_complete.choices" Name="weight_complete" DataType="text" redcap:Variable="weight_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="cardiovascular_complete.choices" Name="cardiovascular_complete" DataType="text" redcap:Variable="cardiovascular_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
        </MetaDataVersion>
        </Study>
        </ODM>';

        $eventMappings = json_decode('[{"arm_num":1,"unique_event_name":"enrollment_arm_1","form":"enrollment"},{"arm_num":1,"unique_event_name":"enrollment_arm_1","form":"contact_information"},{"arm_num":1,"unique_event_name":"enrollment_arm_1","form":"emergency_contacts"},{"arm_num":1,"unique_event_name":"baseline_arm_1","form":"weight"},{"arm_num":1,"unique_event_name":"baseline_arm_1","form":"cardiovascular"},{"arm_num":1,"unique_event_name":"visit_arm_1","form":"weight"},{"arm_num":1,"unique_event_name":"visit_arm_1","form":"cardiovascular"},{"arm_num":1,"unique_event_name":"home_visit_arm_1","form":"weight"},{"arm_num":1,"unique_event_name":"home_visit_arm_1","form":"cardiovascular"},{"arm_num":2,"unique_event_name":"enrollment_arm_2","form":"enrollment"},{"arm_num":2,"unique_event_name":"enrollment_arm_2","form":"contact_information"},{"arm_num":2,"unique_event_name":"enrollment_arm_2","form":"emergency_contacts"},{"arm_num":2,"unique_event_name":"baseline_arm_2","form":"weight"},{"arm_num":2,"unique_event_name":"baseline_arm_2","form":"cardiovascular"},{"arm_num":2,"unique_event_name":"visit_arm_2","form":"weight"},{"arm_num":2,"unique_event_name":"visit_arm_2","form":"cardiovascular"},{"arm_num":2,"unique_event_name":"home_visit_arm_2","form":"weight"},{"arm_num":2,"unique_event_name":"home_visit_arm_2","form":"cardiovascular"},{"arm_num":3,"unique_event_name":"enrollment_arm_3","form":"enrollment"},{"arm_num":3,"unique_event_name":"enrollment_arm_3","form":"contact_information"},{"arm_num":3,"unique_event_name":"enrollment_arm_3","form":"emergency_contacts"},{"arm_num":3,"unique_event_name":"baseline_arm_3","form":"weight"},{"arm_num":3,"unique_event_name":"baseline_arm_3","form":"cardiovascular"},{"arm_num":3,"unique_event_name":"visit_arm_3","form":"weight"},{"arm_num":3,"unique_event_name":"visit_arm_3","form":"cardiovascular"},{"arm_num":3,"unique_event_name":"home_visit_arm_3","form":"weight"},{"arm_num":3,"unique_event_name":"home_visit_arm_3","form":"cardiovascular"}]', true);

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
        . "TABLE,enrollment,root,EVENTS" . "\n"
        . "FIELD,record_id,string" . "\n"
        . "FIELD,registration_date,date" . "\n"
        . "FIELD,first_name,string" . "\n"
        . "FIELD,last_name,string" . "\n"
        . "FIELD,birthdate,date" . "\n"
        . "FIELD,registration_age,string" . "\n"
        . "FIELD,gender,string" . "\n"
        . "FIELD,race,checkbox" . "\n"
        . "\n"
        . "TABLE,contact_information,root,EVENTS" . "\n"
        . "FIELD,home_address,string" . "\n"
        . "FIELD,phone1,string" . "\n"
        . "FIELD,phone_type1,string" . "\n"
        . "FIELD,phone2,string" . "\n"
        . "FIELD,phone_type2,string" . "\n"
        . "FIELD,phone3,string" . "\n"
        . "FIELD,phone_type3,string" . "\n"
        . "FIELD,email,string" . "\n"
        . "\n"
        . "TABLE,emergency_contacts,root,EVENTS" . "\n"
        . "FIELD,em_contact1,string" . "\n"
        . "FIELD,em_contact_phone1a,string" . "\n"
        . "FIELD,em_contact_phone1b,string" . "\n"
        . "FIELD,em_contact2,string" . "\n"
        . "FIELD,em_contact_phone2a,string" . "\n"
        . "FIELD,em_contact_phone2b,string" . "\n"
        . "\n"
        . "TABLE,weight,root,EVENTS" . "\n"
        . "FIELD,weight_time,datetime" . "\n"
        . "FIELD,weight_kg,string" . "\n"
        . "FIELD,height_m,string" . "\n"
        . "\n"
        . "TABLE,weight_repeating_events,root,REPEATING_EVENTS" . "\n"
        . "FIELD,weight_time,datetime" . "\n"
        . "FIELD,weight_kg,string" . "\n"
        . "FIELD,height_m,string" . "\n"
        . "\n"
        . "TABLE,weight_repeating_instruments,root,REPEATING_INSTRUMENTS" . "\n"
        . "FIELD,weight_time,datetime" . "\n"
        . "FIELD,weight_kg,string" . "\n"
        . "FIELD,height_m,string" . "\n"
        . "\n"
        . "TABLE,cardiovascular,root,EVENTS" . "\n"
        . "FIELD,cardiovascular_date,date" . "\n"
        . "FIELD,hdl_mg_dl,string" . "\n"
        . "FIELD,ldl_mg_dl,string" . "\n"
        . "FIELD,triglycerides_mg_dl,string" . "\n"
        . "FIELD,diastolic1,string" . "\n"
        . "FIELD,diastolic2,string" . "\n"
        . "FIELD,diastolic3,string" . "\n"
        . "FIELD,systolic1,string" . "\n"
        . "FIELD,systolic2,string" . "\n"
        . "FIELD,systolic3,string" . "\n"
        . "\n"
        . "TABLE,cardiovascular_repeating_events,root,REPEATING_EVENTS" . "\n"
        . "FIELD,cardiovascular_date,date" . "\n"
        . "FIELD,hdl_mg_dl,string" . "\n"
        . "FIELD,ldl_mg_dl,string" . "\n"
        . "FIELD,triglycerides_mg_dl,string" . "\n"
        . "FIELD,diastolic1,string" . "\n"
        . "FIELD,diastolic2,string" . "\n"
        . "FIELD,diastolic3,string" . "\n"
        . "FIELD,systolic1,string" . "\n"
        . "FIELD,systolic2,string" . "\n"
        . "FIELD,systolic3,string" . "\n"
        . "\n"
        . "TABLE,cardiovascular_repeating_instruments,root,REPEATING_INSTRUMENTS" . "\n"
        . "FIELD,cardiovascular_date,date" . "\n"
        . "FIELD,hdl_mg_dl,string" . "\n"
        . "FIELD,ldl_mg_dl,string" . "\n"
        . "FIELD,triglycerides_mg_dl,string" . "\n"
        . "FIELD,diastolic1,string" . "\n"
        . "FIELD,diastolic2,string" . "\n"
        . "FIELD,diastolic3,string" . "\n"
        . "FIELD,systolic1,string" . "\n"
        . "FIELD,systolic2,string" . "\n"
        . "FIELD,systolic3,string" . "\n"
        . "\n";

        $this->assertSame($rulesText, $result);
    }

    public function testLongitudinalAndRepeatGenerate()
    {
        $projectInfo = json_decode('{"project_id":"14","project_title":"ETL_Data","creation_time":"2018-04-16 13:53:19","production_time":"","in_production":"0","project_language":"English","purpose":"0","purpose_other":"","project_notes":"","custom_record_label":"","secondary_unique_field":"","is_longitudinal":1,"surveys_enabled":"0","scheduling_enabled":"0","record_autonumbering_enabled":"1","randomization_enabled":"0","ddp_enabled":"0","project_irb_number":"","project_grant_number":"","project_pi_firstname":"","project_pi_lastname":"","display_today_now_button":"1","has_repeating_instruments_or_events":1}', true);

        $instruments = json_decode('{"demographics":"Basic Demography Form","sleep_study":"Sleep study"}', true);

        $metadata = json_decode('[{"field_name":"record_id","form_name":"demographics","section_header":"","field_type":"text","field_label":"Study ID","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"first_name","form_name":"demographics","section_header":"Contact Information","field_type":"text","field_label":"First Name","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"last_name","form_name":"demographics","section_header":"","field_type":"text","field_label":"Last Name","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"address","form_name":"demographics","section_header":"","field_type":"notes","field_label":"Street, City, State, ZIP","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"telephone","form_name":"demographics","section_header":"","field_type":"text","field_label":"Phone number","select_choices_or_calculations":"","field_note":"Include Area Code","text_validation_type_or_show_slider_number":"phone","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"email","form_name":"demographics","section_header":"","field_type":"text","field_label":"E-mail","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"email","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"dob","form_name":"demographics","section_header":"","field_type":"text","field_label":"Date of birth","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"date_ymd","text_validation_min":"","text_validation_max":"","identifier":"y","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"age","form_name":"demographics","section_header":"","field_type":"calc","field_label":"Age (years)","select_choices_or_calculations":"rounddown(datediff([dob],\'today\',\'y\'))","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"ethnicity","form_name":"demographics","section_header":"","field_type":"radio","field_label":"Ethnicity","select_choices_or_calculations":"0, Hispanic or Latino | 1, NOT Hispanic or Latino | 2, Unknown \/ Not Reported","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"LH","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"race","form_name":"demographics","section_header":"","field_type":"dropdown","field_label":"Race","select_choices_or_calculations":"0, American Indian\/Alaska Native | 1, Asian | 2, Native Hawaiian or Other Pacific Islander | 3, Black or African American | 4, White | 5, More Than One Race | 6, Unknown \/ Not Reported","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"sex","form_name":"demographics","section_header":"","field_type":"radio","field_label":"Sex","select_choices_or_calculations":"0, Female | 1, Male","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"height","form_name":"demographics","section_header":"","field_type":"text","field_label":"Height (cm)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"number","text_validation_min":"130","text_validation_max":"215","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"weight","form_name":"demographics","section_header":"","field_type":"text","field_label":"Weight (kilograms)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"35","text_validation_max":"200","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"bmi","form_name":"demographics","section_header":"","field_type":"calc","field_label":"BMI","select_choices_or_calculations":"round(([weight]*10000)\/(([height])^(2)),1)","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"comments","form_name":"demographics","section_header":"General Comments","field_type":"notes","field_label":"Comments","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"xxxxxxxxxxxxx","form_name":"demographics","section_header":"","field_type":"text","field_label":"Age","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"number","text_validation_min":"1","text_validation_max":"99","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"favorite_number","form_name":"demographics","section_header":"","field_type":"text","field_label":"Favorite number","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"1","text_validation_max":"999","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"brfss_2009_s4_1","form_name":"sleep_study","section_header":"The next question is about getting enough rest or sleep.","field_type":"radio","field_label":"4.1\tDuring the past 30 days, for about how many days have you felt you did not get enough rest or sleep?","select_choices_or_calculations":"00, Choose to enter number of days | 88, None | 77, Don\'t know \/ Not sure | 99, Refused","field_note":"","text_validation_type_or_show_slider_number":"","text_validation_min":"","text_validation_max":"","identifier":"","branching_logic":"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""},{"field_name":"brfss_2009_s4_1a","form_name":"sleep_study","section_header":"","field_type":"text","field_label":"Number of days (01-30)","select_choices_or_calculations":"","field_note":"","text_validation_type_or_show_slider_number":"integer","text_validation_min":"01","text_validation_max":"30","identifier":"","branching_logic":"[brfss_2009_s4_1]=\"00\"","required_field":"","custom_alignment":"","question_number":"","matrix_group_name":"","matrix_ranking":"","field_annotation":""}]', true);

        $projectXml = '<?xml version="1.0" encoding="UTF-8" ?>
        <ODM xmlns="http://www.cdisc.org/ns/odm/v1.3" xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:redcap="https://projectredcap.org" xsi:schemaLocation="http://www.cdisc.org/ns/odm/v1.3 schema/odm/ODM1-3-1.xsd" ODMVersion="1.3.1" FileOID="000-00-0000" FileType="Snapshot" Description="ETL_Data" AsOfDateTime="2018-11-08T15:48:22" CreationDateTime="2018-11-08T15:48:22" SourceSystem="REDCap" SourceSystemVersion="8.1.10">
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
                <redcap:RepeatingInstrumentsAndEvents>
                        <redcap:RepeatingEvent redcap:UniqueEventName="event_2_arm_1"/>
                        <redcap:RepeatingInstruments>
                                <redcap:RepeatingInstrument redcap:UniqueEventName="event_1_arm_1" redcap:RepeatInstrument="demographics" redcap:CustomLabel=""/>
                        </redcap:RepeatingInstruments>
                </redcap:RepeatingInstrumentsAndEvents>
        </GlobalVariables>
        <MetaDataVersion OID="Metadata.ETLData_2018-11-08_1548" Name="ETL_Data" redcap:RecordIdField="record_id">
                <Protocol>
                        <StudyEventRef StudyEventOID="Event.event_1_arm_1" OrderNumber="1" Mandatory="No"/>
                        <StudyEventRef StudyEventOID="Event.event_2_arm_1" OrderNumber="2" Mandatory="No"/>
                </Protocol>
                <StudyEventDef OID="Event.event_1_arm_1" Name="Event 1" Type="Common" Repeating="No" redcap:EventName="Event 1" redcap:CustomEventLabel="" redcap:UniqueEventName="event_1_arm_1" redcap:ArmNum="1" redcap:ArmName="Arm 1" redcap:DayOffset="1" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.demographics" OrderNumber="1" Mandatory="No" redcap:FormName="demographics"/>
                </StudyEventDef>
                <StudyEventDef OID="Event.event_2_arm_1" Name="Event 2" Type="Common" Repeating="No" redcap:EventName="Event 2" redcap:CustomEventLabel="" redcap:UniqueEventName="event_2_arm_1" redcap:ArmNum="1" redcap:ArmName="Arm 1" redcap:DayOffset="2" redcap:OffsetMin="0" redcap:OffsetMax="0">
                        <FormRef FormOID="Form.demographics" OrderNumber="1" Mandatory="No" redcap:FormName="demographics"/>
                </StudyEventDef>
                <FormDef OID="Form.demographics" Name="Basic Demography Form" Repeating="No" redcap:FormName="demographics">
                        <ItemGroupRef ItemGroupOID="demographics.record_id" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.first_name" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.last_name" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.comments" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.xxxxxxxxxxxxx" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="demographics.demographics_complete" Mandatory="No"/>
                </FormDef>
                <FormDef OID="Form.sleep_study" Name="Sleep study" Repeating="No" redcap:FormName="sleep_study">
                        <ItemGroupRef ItemGroupOID="sleep_study.brfss_2009_s4_1" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="sleep_study.brfss_2009_s4_1a" Mandatory="No"/>
                        <ItemGroupRef ItemGroupOID="sleep_study.sleep_study_complete" Mandatory="No"/>
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
                <ItemGroupDef OID="demographics.xxxxxxxxxxxxx" Name="Basic Demography Form" Repeating="No">
                        <ItemRef ItemOID="xxxxxxxxxxxxx" Mandatory="No" redcap:Variable="xxxxxxxxxxxxx"/>
                        <ItemRef ItemOID="favorite_number" Mandatory="No" redcap:Variable="favorite_number"/>
                </ItemGroupDef>
                <ItemGroupDef OID="demographics.demographics_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="demographics_complete" Mandatory="No" redcap:Variable="demographics_complete"/>
                </ItemGroupDef>
                <ItemGroupDef OID="sleep_study.brfss_2009_s4_1" Name="The next question is about getting enough rest or sleep." Repeating="No">
                        <ItemRef ItemOID="brfss_2009_s4_1" Mandatory="No" redcap:Variable="brfss_2009_s4_1"/>
                </ItemGroupDef>
                <ItemGroupDef OID="sleep_study.brfss_2009_s4_1a" Name="Sleep study" Repeating="No">
                        <ItemRef ItemOID="brfss_2009_s4_1a" Mandatory="No" redcap:Variable="brfss_2009_s4_1a"/>
                </ItemGroupDef>
                <ItemGroupDef OID="sleep_study.sleep_study_complete" Name="Form Status" Repeating="No">
                        <ItemRef ItemOID="sleep_study_complete" Mandatory="No" redcap:Variable="sleep_study_complete"/>
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
                <ItemDef OID="xxxxxxxxxxxxx" Name="xxxxxxxxxxxxx" DataType="float" Length="999" SignificantDigits="1" redcap:Variable="xxxxxxxxxxxxx" redcap:FieldType="text" redcap:TextValidationType="float">
                        <Question><TranslatedText>Age</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>1</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (1 - 99). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>99</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (1 - 99). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="favorite_number" Name="favorite_number" DataType="integer" Length="999" redcap:Variable="favorite_number" redcap:FieldType="text" redcap:TextValidationType="int">
                        <Question><TranslatedText>Favorite number</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>1</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (1 - 999). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>999</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (1 - 999). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="demographics_complete" Name="demographics_complete" DataType="text" Length="1" redcap:Variable="demographics_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="demographics_complete.choices"/>
                </ItemDef>
                <ItemDef OID="brfss_2009_s4_1" Name="brfss_2009_s4_1" DataType="text" Length="2" redcap:Variable="brfss_2009_s4_1" redcap:FieldType="radio" redcap:SectionHeader="The next question is about getting enough rest or sleep.">
                        <Question><TranslatedText>4.1   During the past 30 days, for about how many days have you felt you did not get enough rest or sleep?</TranslatedText></Question>
                        <CodeListRef CodeListOID="brfss_2009_s4_1.choices"/>
                </ItemDef>
                <ItemDef OID="brfss_2009_s4_1a" Name="brfss_2009_s4_1a" DataType="integer" Length="999" redcap:Variable="brfss_2009_s4_1a" redcap:FieldType="text" redcap:TextValidationType="int" redcap:BranchingLogic="[brfss_2009_s4_1]=&quot;00&quot;">
                        <Question><TranslatedText>Number of days (01-30)</TranslatedText></Question>
                        <RangeCheck Comparator="GE" SoftHard="Soft">
                                <CheckValue>01</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (01 - 30). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                        <RangeCheck Comparator="LE" SoftHard="Soft">
                                <CheckValue>30</CheckValue>
                                <ErrorMessage><TranslatedText>The value you provided is outside the suggested range. (01 - 30). This value is admissible, but you may wish to verify.</TranslatedText></ErrorMessage>
                        </RangeCheck>
                </ItemDef>
                <ItemDef OID="sleep_study_complete" Name="sleep_study_complete" DataType="text" Length="1" redcap:Variable="sleep_study_complete" redcap:FieldType="select" redcap:SectionHeader="Form Status">
                        <Question><TranslatedText>Complete?</TranslatedText></Question>
                        <CodeListRef CodeListOID="sleep_study_complete.choices"/>
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
                <CodeList OID="brfss_2009_s4_1.choices" Name="brfss_2009_s4_1" DataType="text" redcap:Variable="brfss_2009_s4_1">
                        <CodeListItem CodedValue="00"><Decode><TranslatedText>Choose to enter number of days</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="88"><Decode><TranslatedText>None</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="77"><Decode><TranslatedText>Don&#039;t know / Not sure</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="99"><Decode><TranslatedText>Refused</TranslatedText></Decode></CodeListItem>
                </CodeList>
                <CodeList OID="sleep_study_complete.choices" Name="sleep_study_complete" DataType="text" redcap:Variable="sleep_study_complete">
                        <CodeListItem CodedValue="0"><Decode><TranslatedText>Incomplete</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="1"><Decode><TranslatedText>Unverified</TranslatedText></Decode></CodeListItem>
                        <CodeListItem CodedValue="2"><Decode><TranslatedText>Complete</TranslatedText></Decode></CodeListItem>
                </CodeList>
        </MetaDataVersion>
        </Study>
        </ODM>';

        $eventMappings = json_decode('[{"arm_num":1,"unique_event_name":"event_1_arm_1","form":"demographics"},{"arm_num":1,"unique_event_name":"event_2_arm_1","form":"demographics"}]', true);

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
        . "TABLE,demographics_repeating_events,root,REPEATING_EVENTS" . "\n"
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
        . "FIELD,xxxxxxxxxxxxx,string" . "\n"
        . "FIELD,favorite_number,string" . "\n"
        . "\n"
        . "TABLE,demographics_repeating_instruments,root,REPEATING_INSTRUMENTS" . "\n"
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
        . "FIELD,xxxxxxxxxxxxx,string" . "\n"
        . "FIELD,favorite_number,string" . "\n"
        . "\n";

        $this->assertSame($rulesText, $result);
    }
}