<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Logger.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/PayPalTx.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/ProjectFile.php');

$logger->info('client ip: ' . $_SERVER['REMOTE_ADDR']);


// TODO - in case of error: show friendly error page with instructions for help

// get project info
$project_id = get_numeric_param('tid');

if (!$project_id) {
    $logger->warn('project_id param missing!');
    echo 'INVALID REQUEST (5)';
    exit;
}

$project = Project::fetch_for_id($project_id);
if (!$project || !$project->id) {
    $logger->warn('project not found for id: ' . $project_id);
    echo 'INVALID REQUEST (7)';
    exit;
}

if (get_param('mode') == 'purchase') {
    start_paypal_flow();
} else {
    show_file_selection(true);
}

exit;

function start_paypal_flow() {
    global $logger;

    // read the post from PayPal system and add 'cmd'
    $req = 'cmd=_notify-validate';

    foreach ($_POST as $key => $value) {
        $value = urlencode(stripslashes($value));
        $req .= "&$key=$value";
    }

    // post back to PayPal system to validate
    $header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
    $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
    $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

    $logger->info('posting transaction data back to paypal ...');

    $fp = '';
    if ($GLOBALS['SANDBOX_MODE']) {
        $logger->info('sending verification request to www.sandbox.paypal.com ...');
        $fp = fsockopen ('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
    } else {
        $logger->info('sending verification request to www.paypal.com ...');
        $fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);
    }

    if (!$fp) {
        $logger->error('http error!');
        echo 'HTTP ERROR';
        exit;

    } else {
        fputs ($fp, $header . $req);
        while (!feof($fp)) {
            $res = @fgets ($fp, 1024);
            if (strcmp ($res, "VERIFIED") == 0) {
                $create_tx = validate_request_data();
                if ($create_tx) {
                    create_paypal_tx();
                }
                show_file_selection(false);

            } else if (strcmp ($res, "INVALID") == 0) {
                // log for manual investigation
                $logger->warn('******** transaction data was not verified by paypal! investigate manually! ********');

                // echo the response
                echo 'INVALID REQUEST (3)';
                exit;
            }
        }

        fclose ($fp);
    }
}

function validate_request_data() {
    global $logger;
    global $project;

    $logger->info('validating request data ...');

    // check that payment_status is 'Completed'
    if ($_POST['payment_status'] != 'Completed') {
        $logger->warn('payment status is not "Completed"!');
        echo 'INVALID REQUEST (1)';
        exit;
    }

    // check that txn_id has not been previously processed under a different ip address
    $create_new_tx = true;
    $paypal_tx = PayPalTx::fetch_for_paypal_tx_id(get_param('txn_id'));
    if ($paypal_tx && $paypal_tx->id) {
        $create_new_tx = false;
        if ($paypal_tx->payer_ip != $_SERVER['REMOTE_ADDR']) {
            $logger->warn('transaction has already been processed with another ip address!');
            echo 'INVALID REQUEST (9)';
            exit;
        }
    }

    // check that receiver_email is Primary PayPal email
    if ($_POST['receiver_email'] != $GLOBALS['SELLER_EMAIL']) {
        $logger->warn('receiver email is not seller email!');
        echo 'INVALID REQUEST (2)';
        exit;
    }

//    // check price/currency // FIXME - no project price available anymore
//    if ($_POST['mc_gross'] < $project->price) {
//        $logger->warn('transaction price is lower than project price!');
//        echo 'INVALID REQUEST (8)';
//        exit;
//    }
    if ($_POST['mc_currency'] != $project->currency) {
        $logger->warn('currency mismatch!');
        echo 'INVALID REQUEST (10)';
        exit;
    }

    return $create_new_tx;
}

function create_paypal_tx() {
    global $logger;
    $logger->info('creating paypal tx ...');

    // create tx record
    $p = new PayPalTx();
    $p->payer             = $_POST['payer_email'];
    $p->payer_ip          = $_SERVER['REMOTE_ADDR'];
    $p->receiver          = $_POST['receiver_email'];
    $p->paypal_tx_id      = get_param('txn_id');
    $p->item_number       = $_POST['item_number'];
    $p->amount            = $_POST['mc_gross'];
    $p->currency          = $_POST['mc_currency'];
    $p->first_name        = get_param('first_name');
    $p->last_name         = get_param('last_name');
    $p->residence_country = $_POST['residence_country'];
    $p->insert();
}

function show_file_selection($isFreeDownload) {
    global $logger;
    global $project;

    $logger->info('showing file selection ...');

    $user = User::fetch_for_id($project->user_id);
    if (!$user || !$user->id) {
        $logger->warn('user not found for id: ' . $project->user_id);
        echo 'INVALID REQUEST (11)';
        exit;
    }

    // display list of different files for download

    writePageDoctype();
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <?php writePageMetaTags(); ?>
    <title><?php writePageTitle(); ?></title>
    <link rel="stylesheet" href="../Styles/main.css" type="text/css">
    <script type="text/javascript">

var fileSelected = false;

    </script>
  </head>
  <body>
    <center>
      <div id="spacer">
      </div>

      <!--<div id="podperfect_headline">
        <span class="headline">oneloudr.com</span><br>
        Music Collaboration and Licensing
      </div>-->

      <div id="content_info">
<?php

    echo '<br><br>';
    echo '<span class="subheadline">' . $project->title . '</span>';
    echo '<br>';
    echo '<span class="subsubheadline">' . $user->name . '</span>';
    echo '<br><br>';

?>
      </div>

      <div id="selection">
        <span class="subsubheadline">Please select download file:</span><br><br>
        <form name="fileSelectionFrm" action="downloadFile.php" method="POST">
          <input type="hidden" name="mode" value="<?php echo get_param('mode'); ?>">
          <input type="hidden" name="transactionId" value="<?php echo get_param('txn_id'); ?>">
          <input type="hidden" name="project_id" value="<?php echo $project->id; ?>">

          <div id="selection_list">
<?php

    $files = ProjectFile::fetch_all_for_project_id($project->id, false);
    foreach ($files as $file) {
        echo '<input type="radio" name="atfid" value="' . $file->id . '" onClick="fileSelected=true; checkInputs();">&nbsp;' . escape($file->orig_filename . ' (' . $file->type . ')') . '<br>' . "\n";
    }

?>
          </div>
          <br>
<?php

    if (count($files) == 0) {
        echo '<i>Whoops, no files are available for download!</i>';

    } else {
        echo '<input type="submit" id="dld" value="&nbsp;Download&nbsp;" disabled="true">';
        if ($isFreeDownload) {
            echo '<br><br><input type="checkbox" id="legalTermsCheckbox" onClick="checkInputs();">&nbsp;I agree to the terms below.';
        }
    }

?>

        </form>
      </div>

<?php

if ($isFreeDownload) {

?>
      <div id="legalTermsForDownload">
        <h1>Terms and conditions</h1>
        <br>
        <textarea readonly="true" rows="20" cols="80">

Remix Agreement

"You have asked to download a song for the purpose of remixing it, or to simply use for non-commercial use. If you intend to participate in collaboration with another artist,  you may create a remix or use the song for non-commercial use under a Non-Commercial Creative Commons license, only if you agree to the following terms.  To agree to these terms, you must click "I accept."

This Agreement sets forth your rights and obligations regarding the remix so you should read it.  Among the important things it says are the following:  If you make a remix, you must upload it to oneloudr, where it will be distributed and licensed according to the oneloudr Artist terms <LINK>.  oneloudr will distribute it to individuals free of charge for personal, non-commercial use under a Creative Commons license.  oneloudr will also offer the remix for commercial licensing, and any fees will be split between you and the original artist after oneloudr takes its fees.  You and original artist will be co-owners of the remix and will be required to split any revenue from it.  The details of these terms are set forth below in the Agreement."



This agreement "Agreement" sets forth the terms under which oneloudr authorizes you "New Artist" to create a remixed version of an Original Recording that has been posted on the oneloudr service.  The Original Artist has licensed to oneloudr certain rights with respect to the Original Song with the right to further sublicense rights to other artists, such as the New Artist.  For good and valuable consideration, the receipt of which is acknowledged, the parties agree as follows.

1.	Definitions

Media:  any recording or distribution medium, now existing or later developed in any format or version.

New Master:   the sound recording based on the Original Master created by New Artist by sampling, modifying, or adding instrumental or vocal tracks to the Original Master.

New Composition:  the musical composition that is a derivative version of the Original Composition and that is embodied in the New Master.

New Song:  collectively, the New Composition and the New Master.

Original Artist:  the author or authors of the Original Composition.

Original Composition:  the musical composition authored by Original Artist embodied in the Original Recording.

Original Recording:  the sound recording that (a) was created by Original Artist; (b) posted on the oneloudr Service; and (c) downloaded by New Artist for use pursuant to this Agreement.

2.	License

a.	Grant.  oneloudr grants New Artist a non-exclusive and non-transferable right, in accordance with the terms of this Agreement, to copy the Original Song as necessary for use under this Agreement and to create a single derivative work of the Original Song by modifying the Original Recording through remixing, sampling, adding additional vocal or instrumental tracks, or otherwise changing it.

b.	Restrictions on Grant.  Notwithstanding the foregoing, the intent of the license set forth in this Section is to facilitate the creation of a significantly modified work that recasts, transforms, adapts, or adds to the Original Composition.  Changes unlikely to be perceived as significant by listeners (by way of example, but not limited to, adding a few seconds of silence to the beginning or end of the track) violate the terms of the license set forth in this Section 2 and will thus result in New Artist gaining no rights pursuant to Section 3 of this Agreement.

3.	Intellectual Property Rights in New Song

a.	Ownership of New Master and New Composition.  The New Artist and Original Artist are joint authors of the New Master and New Composition and co-owners of the copyrights therein.  Any registration of the New Master or New Composition with any governmental copyright office, including, but not limited to the U.S. Copyright Office, or with any collecting society, including, but not limited to ASCAP, BMI, SESAC, or SoundExchange, shall acknowledge such joint authorship and co-ownership.  The joint authorship will be attributed and acknowledged in connection with any publication of the New Song.

b.	License to oneloudr.   New Artist will promptly upload the New Master, and in any event, within no more than 30 days after completion, to the oneloudr Service.  New Artist grants to oneloudr a non-exclusive, transferable, right to reproduce, distribute, publicly perform, and sublicense the New Song in all Media pursuant to the oneloudr Artist Agreement <LINK>, which is hereby incorporated by reference.

c.	Trademarks and Right of Publicity.  New Artist hereby grants oneloudr and Original Artist a non-exclusive, assignable and sub-licensable license to reproduce and display New Artists trademarks, service marks, logos, and any elements associated with the identity of New Artist including, but not limited to New Artists name, likeness, or signature, for the purpose of identifying the New Artist or any portion thereof in connection with (a) the promotion and marketing of the New Song in any Media and  (b) the promotion of the oneloudr website and business.

d.	Original Song.  For the avoidance of doubt, New Artist acknowledges that this Agreement grants New Artist no rights in, and creates no obligations with respect to, the Original Song or any derivative work based on the Original Song other than the rights needed to create and exploit the New Master and New Composition pursuant to this Agreement.

4.	 Exploitation of the New Song

a.	oneloudr Royalties.  oneloudr will divide all money payable pursuant to the oneloudr Artist Agreement <LINK>, which is hereby incorporated by reference, pertaining to the New Song evenly (50/50) between New Artist and Original Artist.

b.	Other Revenue.  Unless otherwise agreed by the New Artist and Original Artist, the New Artist agrees to pay the Original Artist 50% of all gross income from commercial exploitation of the New Song.  For the avoidance of doubt, Original Artist has agreed to a reciprocal obligation to New Artist pursuant to the oneloudr Artist Agreement <LINK>, which is hereby incorporated by reference.  Unless otherwise agreed by the New Artist and Original Artist, each will bear its own expenses, taxes, and other costs associated with creating and exploiting the New Song.

c.	Further Exploitation of New Song.  The New Artist will reasonably cooperate with the Original Artist to exploit the New Song.  In any event, except as set forth in the oneloudr Artist Agreement, exploitation of the New Song, including, but not limited to securing publishing and performance royalties, is the mutual responsibility of New Artist and Original Artist and not oneloudr.

5.	WARRANTIES

a.	Authority.  New Artist represents and warrants that: (i) it has the authority to enter into this Agreement, (ii) it has the right to provide the New Song (subject only to Original Artists rights) and grant licenses therein to oneloudr, and (iii) all rights granted to oneloudr will be free of any claims, liens or conflicting rights in favor of any third party other than Original Artist.

b.	Clearance.  New Artist represents and warrants that it has obtained all releases, consents, and permissions required with respect to the New Song necessary for the execution and performance of this Agreement and for the commercial exploitation of the New Song.

c. 	Legal Status of New Song.  New Artist represents and warrants that the New Song will not contain material that is defamatory, libelous, obscene, indecent, or illegal or that violates any right of confidentiality, privacy or publicity of any third party or that violates any copyright, trademark, trade secret, or other intellectual property right of any third party.

d.	Remedies.  With respect to any breach of warranty provided under this Section 5, New Artist shall at oneloudr's option and request, in addition to any other remedy provided under this Agreement and at no additional cost to oneloudr, replace or modify the New Song to correct any noncomformity.

6.	INDEMNIFICATION

a.	Indemnity.  New Artist agrees to defend, indemnify, and hold oneloudr and the Original Artist harmless against any loss, cost, liability, and expense (including reasonable attorneys' fees) arising from any breach of the representations and warranties set forth in Section 5.

b.	Settlement.  New Artist shall not, without the prior written consent of oneloudr, settle, compromise or consent to the entry of any judgment with respect to any pending or threatened claim covered by the indemnity provided in this Section unless the settlement, compromise or consent provides for and includes an express, unconditional release of all claims, damages, liabilities, costs and expenses, including reasonable legal fees and expenses, against oneloudr.

7.	NO CONSEQUENTIAL DAMAGES

UNDER NO CIRCUMSTANCES WILL ONELOUDR BE LIABLE FOR CONSEQUENTIAL, INDIRECT, SPECIAL, PUNITIVE OR INCIDENTAL DAMAGES OR LOST PROFITS, WHETHER FORESEEABLE OR UNFORESEEABLE, BASED ON CLAIMS ARISING OUT OF BREACH OR FAILURE OF EXPRESS OR IMPLIED WARRANTY, BREACH OF CONTRACT, MISREPRESENTATION, NEGLIGENCE, STRICT LIABILITY IN TORT OR OTHERWISE.  IN NO EVENT WILL THE AGGREGATE LIABILITY THAT ONELOUDR MAY INCUR IN ANY ACTION OR PROCEEDING EXCEED THE LESSER OF $100 OR THE TOTAL AMOUNT OF ROYALTIES PAID BY ONELOUDR DURING THE PAST YEAR OF THE CURRENT TERM.  THE LIMITATIONS, EXCLUSIONS AND DISCLAIMERS SET FORTH IN THIS SECTION 7 WILL NOT APPLY ONLY IF AND TO THE EXTENT THAT THE LAW OR A COURT OF COMPETENT JURISDICTION REQUIRES LIABILITY UNDER APPLICABLE LAW BEYOND AND DESPITE THESE LIMITATIONS, EXCLUSIONS AND DISCLAIMERS.

8.	TERM AND TERMINATION

This Agreement will become effective as of the Effective Date, and will remain in effect for a one year term.  After each then-current Term, the Agreement shall automatically renew for successive Terms unless either party provides at least thirty (30) days written notice of non-renewal prior to the end of the then-current Term.

9.	CONSEQUENCES OF TERMINATION

Upon expiration or termination of this Agreement for any reason, oneloudr shall have no further obligation to New Artist pursuant to this Agreement.  Sections 5, 6, 7, 8, 9, 10, 11, 12, 13, 14 and 15 will survive the expiration or termination of this Agreement.

10.	INDEPENDENT PARTIES

New Artist is an independent contractor and shall be solely responsible for any unemployment or disability insurance payments, or any social security, income tax or other withholdings, deductions or payments which may be required by national or local law with respect to any sums paid New Artist hereunder.  Neither this Agreement, nor any terms and conditions contained herein, shall be construed as creating a partnership, joint venture, agency relationship or other fiduciary relationship between the parties or granting a franchise between the parties.  New Artist is not oneloudr's agent or representative and has no authority to bind or commit oneloudr to any agreements or other obligations.

11.	NOTICES

All notices and requests in connection with this Agreement shall be deemed given as of the day they are received either by receipted, nationwide overnight delivery service, or in the U.S. mails, postage prepaid, certified or registered, return receipt requested, to the address specified on the first page of this Agreement to the attention of the New Artist representative and to the attention of the oneloudr representative designated in this Agreement or to any other address that may be designated by prior notice.

12.	ASSIGNMENT

New Artist may not assign, delegate, sub-contract or otherwise transfer this Agreement or any of its rights or obligations hereunder without the express written permission of oneloudr. Any assignment in violation of this Section shall be void and unenforceable.  oneloudr may assign, delegate, sub-contract or transfer this Agreement or any of its rights or obligations hereunder.   New Artist hereby consents in advance to any such assignment, subcontract, or transfer.

13.	THIRD PARTY BENEFICIARY

New Artist acknowledges that the provisions of this Agreement are intended to inure to the benefit of the Original Artist as a third party beneficiary of this Agreement, and the Original Artist will be entitled to enforce such provisions against New Artist.  New Artist further acknowledges that the Original Artist accepts its third party beneficiary rights hereunder and that such rights will be deemed irrevocable.

14.	ARBITRATION

Any controversy or claim arising out of or relating to this contract, or the breach thereof, shall be settled by arbitration administered by the American Arbitration Association under its Commercial Arbitration Rules, and judgment on the award rendered by the arbitrator(s) may be entered in any court having jurisdiction thereof.  To initiate arbitration, either party will file the appropriate notice at the Regional Office of the AAA in Nashville, Tennessee, U.S.A.  The arbitration proceeding will take place AAA in Nashville, Tennessee, U.S.A.  The arbitral award will be the exclusive remedy of the parties for all claims, counterclaims, issues or accountings presented or plead to the arbitrators.  Any additional costs, fees or expenses incurred in enforcing the arbitral award will be charged against the party that resists its enforcement.  Nothing in this Section will prevent the parties from seeking interim injunctive relief against one another.

15.	GENERAL

This Agreement will be governed by and interpreted in accordance with the laws of Tennessee, excluding its conflict of law principles.  The parties hereby submit to the jurisdiction of the state or federal courts located in Nashville, Tennessee, waiving any objection to forum non conveniens.  This Agreement constitutes the complete and entire statement of all terms, conditions and representations of the agreement between New Artist and oneloudr with respect to its subject matter and supersedes all prior writings or understanding. Except as otherwise provided above, any waiver, amendment or other modification of this Agreement will not be effective unless in writing and signed by the party against whom enforcement is sought. If any provision of this Agreement is held to be unenforceable, in whole or in part, such holding will not affect the validity of the other provisions of this Agreement.  No provision of this Agreement, nor any ambiguities that may be contained herein, shall be construed against any party on the ground that such party or its counsel drafted the provision at issue or that the provision at issue contains a covenant, representation or warranty of such party.  All rights and remedies of the parties set forth in this Agreement shall be cumulative, and none shall exclude any other right or remedy allowed by applicable law.





        </textarea><br>
      </div>
      <br><br><br><br><br><br><br><br><br>
<?php

} // end of if ($isFreeDownload)

?>

    </center>

    <?php writeGoogleAnalyticsStuff(); ?>

  </body>

  <script type="text/javascript">

function checkInputs() {
    var dldBtnEnabled = fileSelected;
    if (document.getElementById('legalTermsCheckbox')) {
        if (!document.getElementById('legalTermsCheckbox').checked) {
            dldBtnEnabled = false;
        }
    }
    if (dldBtnEnabled) {
        document.fileSelectionFrm.dld.disabled = false;
    } else {
        document.fileSelectionFrm.dld.disabled = true;
    }
}

  </script>
</html>
<?php

} // end of function show_file_selection()

?>


