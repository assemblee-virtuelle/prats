<?php
/**
 * Created by PhpStorm.
 * User: weeger
 * Date: 27/02/2017
 * Time: 15:49
 */

namespace semappsBundle;

/** TODO remove this file */
class semappsConfig
{
    //class
    const URI_PAIR_PERSON = 'http://virtual-assembly.org/pair#Person';
    const URI_PAIR_ORGANIZATION ='http://virtual-assembly.org/pair#Organization';
    const URI_PAIR_PROJECT ='http://virtual-assembly.org/pair#Project';
    const URI_PAIR_EVENT ='http://virtual-assembly.org/pair#Event';
    const URI_PAIR_PROPOSAL = 'http://virtual-assembly.org/pair#Proposal';
    const URI_PAIR_DOCUMENT = 'http://virtual-assembly.org/pair#Document';
    const URI_PAIR_DOCUMENT_TYPE = 'http://virtual-assembly.org/pair#DocumentType';
    const URI_PAIR_PROJECT_TYPE = 'http://virtual-assembly.org/pair#ProjectType';
    const URI_PAIR_EVENT_TYPE = 'http://virtual-assembly.org/pair#EventType';
    const URI_PAIR_PROPOSAL_TYPE = 'http://virtual-assembly.org/pair#ProposalType';
    const URI_PAIR_ORGANIZATION_TYPE = 'http://virtual-assembly.org/pair#OrganizationType';
    const URI_MIXTE_PERSON_ORGANIZATION = [
        self::URI_PAIR_PERSON,
        self::URI_PAIR_ORGANIZATION,
    ];
    const URI_ALL_PAIR_EXCEPT_DOC_TYPE = [
        self::URI_PAIR_PERSON,
        self::URI_PAIR_ORGANIZATION,
        self::URI_PAIR_PROJECT,
        self::URI_PAIR_EVENT,
        self::URI_PAIR_PROPOSAL,
        self::URI_PAIR_DOCUMENT,

    ];
    //thesaurus
    const URI_SKOS_THESAURUS = 'http://www.w3.org/2004/02/skos/core#Concept';

    const Multiple = '';
    const PREFIX = 'urn:semapps/contacts/row/';

}
