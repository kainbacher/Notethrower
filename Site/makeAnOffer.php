<?php

include_once('../Includes/Init.php');
include_once('../Includes/PermissionsUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/Mailer/MailUtil.php');

$problemOccured = false;
$errorFields = Array();

$trackId = get_numeric_param('tid');

$track  = Project::fetch_for_id($trackId);
$user   = User::fetch_for_id($track->user_id);

$offer->email = '';
$offer->usage = '';
$offer->price = '';
$offer->accepted = false;
$offer->emailArtistOffer = '';
$offer->usageArtistOffer = '';

$offerSent = false;

if (get_param('action') == 'send') {
    if (!inputDataOk($errorFields, $offer)) {
        $problemOccured = true;
    } else {
        // send offer as email
        $text = $offer->email . ' made an offer for your song "' . $track->title . '" on oneloudr.com.' . "\n" .
                'Usage for your song: ' . $offer->usage . "\n" .
                'Category: ' . $offer->usageCategory . "\n" .
                'Offer in $: ' . $offer->price . "\n" . "\n" .
                'Your oneloudr team';
        sendEmail($user->email_address, 'New offer for song on oneloudr.com', $text);
        sendEmail($GLOBALS['SELLER_EMAIL'], 'New offer for song on oneloudr.com', $text);
        $offerSent = true;
    }
} else if (get_param('action') == 'checkout') {

    if (!inputDataOkForArtistOffer($errorFields, $offer)) {
        $problemOccured = true;
    } else {
        // send an email with the checkout info
        $text = 'Someone (email: ' . $offer->emailArtistOffer . ') has accepted your offer for track "' . $track->title . '" on oneloudr.com.' . "\n" .
                'Usage for your song: ' . $offer->usageArtistOffer . "\n" .
                'Category: ' . $offer->usageCategoryArtistOffer . "\n" .
                'Once the user finishes the paypal transaction, you should receive from paypal a message about the purchase' . "\n\n" .
                'Your oneloudr team';
        sendEmail($user->email_address, 'Song checkout on oneloudr.com', $text);
        sendEmail($GLOBALS['SELLER_EMAIL'], 'Song checkout on oneloudr.com', $text);
        //email sent, redirect to paypal
        $paypalUrl = $GLOBALS['PAYPAL_BASE_URL'] . '&business=' . $GLOBALS['SELLER_EMAIL'];
        $paypalUrl .= '&item_name=' . urlencode($user->name . ' - ' . $track->title);
        $paypalUrl .= '&cbt=' . urlencode(substr('Download: ' . $user->name . ' - ' . $track->title, 0, 60));
        $paypalUrl .= '&item_number=PPI' . $track->id;
        $paypalUrl .= '&amount=' . $track->price;
        $paypalUrl .= '&currency_code=' . $track->currency;
        $paypalUrl .= '&return=' . urlencode($GLOBALS['RETURN_URL'] . '?tid=' . $track->id . '&mode=purchase');
        $paypalUrl .= '&cancel_return=' . urlencode($GLOBALS['BASE_URL']);
        $paypalUrl .= $GLOBALS['PAYPAL_FIX_PARAMS'];
        header( 'Location: ' . $paypalUrl ) ;
    }
}

writePageDoctype();

?>
<html>
    <? include ("headerData.php"); ?>
    <script type="text/javascript">

        var termsAccepted = false;

        function acceptTerms() {
            termsAccepted = true;
        }

        function showCommercialLicenseeTerms() {
            window.open('commercialLicenseeTerms.php','TERMS_AND_CONDITIONS','scrollbars=yes,resizable=yes,status=0,width=1100,height=600');
        }

  </script>
  </head>
  <body>
    <div id="bodyWrapper">

        <? include ("pageHeader.php"); ?>
        <? include ("mainMenu.php"); ?>

    <div id="pageMainContent">


        <div class="horizontalMenu">
            <ul>
                <li><a href="index.php">Startpage</a></li>
            </ul>
        </div>

<?php

// display the form only if the offer is not sent yet:
if (!$offerSent) {

    if ($track->price > 0) {



?>

            <div id="trackFormDiv">
                <div id="container">

                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                   <input type="hidden" name="action" value="checkout">
                   <input type="hidden" name="tid" value="<?php echo $track->id; ?>">

                    <br/>
                    <h1>Artists Offer:</h1>

                    <br/>
                    <br/>

                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Artist Name</p>
                        </div>
                        <div class="makeAnOfferRight">
                            <p><?php echo $user->name ?></p>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Track Name</p>
                        </div>
                        <div class="makeAnOfferRight">
                            <p><?php echo $track->title ?></p>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Price</p>
                        </div>
                        <div class="makeAnOfferRight">
                            <p><?php echo '$' . $track->price ?></p>
                        </div>
                        <div class="clear"></div>
                    </div>

                   <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Your Email</p>
                        </div>
                        <div class="makeAnOfferRight">

<?php
        echo '<input class="' . (isset($errorFields['emailArtistOffer']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="text" name="emailArtistOffer" maxlength="255" size="40"';
        echo ' value="';
        $value = $offer->emailArtistOffer;
        echo ($preconfiguredValue && !$value ? escape($preconfiguredValue) : escape($value));
        echo '">' . "\n";

?>

                        </div>
                        <div class="makeAnOfferInfo">
                            <p>Please specify your email address, so that we can contact you.</p>
                        </div>
                        <div class="clear"></div>
                    </div>



                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Song usage</p>
                        </div>
                        <div class="makeAnOfferRight">

<?php
        echo '<select class="' . (isset($errorFields['usageArtistOffer']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usageCategoryArtistOffer" style="width: 255px">';
        $selectedValue = $offer->usageCategoryArtistOffer;
        $selectOptions = array(-1 => '- Please choose -') + $GLOBALS['OFFER_CATEGORIES'];
        foreach (array_values($selectOptions) as $optVal) {
            $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
            echo '<option value="' . $optVal . '"' . $selected . '>' . escape($optVal) . '</option>' . "\n";
        }
        echo '</select>' . "\n";

        echo '<textarea class="' . (isset($errorFields['usageArtistOffer']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usageArtistOffer" rows="6" cols="40">';
        $value = $offer->usageArtistOffer;
        echo escape($value);
        echo '</textarea>' . "\n";
?>

                        </div>
                        <div class="makeAnOfferInfo">
                            <p>Please describe the use (the "Use") you wish to make of the song.
                                Please choose one of the following options and then type a brief description of your plans,
                                including a description of the work (the "Work").
                                Please note that your choice and description of your Use and Work are important because we
                                only license the song to you for a single Use in a particular Work.
                                Additional Works require new licenses.
                            </p>
                        </div>
                        <div class="clear"></div>
                    </div>



                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Secure chekout</p>
                        </div>
                        <div class="makeAnOfferRight">
                            <img src="../Images/paypal_logo.gif" alt="paypal_logo" width="114" height="31" />
                        </div>
                        <div class="clear"></div>
                    </div>


                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            &nbsp;
                        </div>
                        <div class="makeAnOfferRight">
                            <br/>
                            <input type="submit" class="button" value="Accept this Offer and pay now">
                        </div>
                        <div class="clear"></div>
                    </div>

                    <br/>




                    </form>
                </div> <!-- container -->
            </div> <!-- trackFormDiv -->

        <br/>

        <?php

    } // if ($track->price > 0)

    ?>


        <div id="trackFormDiv">
            <div id="container">

                <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" enctype="multipart/form-data">
                   <input type="hidden" name="action" value="send">
                   <input type="hidden" name="tid" value="<?php echo $track->id; ?>">

                    <br/>
                    <h1>Make <?php if ($track->price > 0) { echo 'a different ';} else { echo 'an '; } ?>Offer:</h1>

                    <br/>
                    <br/>

                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferBroad">
                            <p>Thank you for your interest in licensing the song <b><?php echo $track->title; ?></b>.
                    To license the song, you must complete this form and agree to the terms of our standard license agreement.</p>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Your Email</p>
                        </div>
                        <div class="makeAnOfferRight">

<?php
        echo '<input class="' . (isset($errorFields['email']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="text" name="email" maxlength="255" size="40"';
        echo ' value="';
        $value = $offer->email;
        echo ($preconfiguredValue && !$value ? escape($preconfiguredValue) : escape($value));
        echo '">' . "\n";

?>

                        </div>
                        <div class="makeAnOfferInfo">
                            <p>Please specify your email address, so that we can contact you.</p>
                        </div>
                        <div class="clear"></div>
                    </div>



                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Song usage</p>
                        </div>
                        <div class="makeAnOfferRight">

<?php
        echo '<select class="' . (isset($errorFields['usage']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usageCategory" style="width: 255px">';
        $selectedValue = $offer->usageCategory;
        $selectOptions = array(-1 => '- Please choose -') + $GLOBALS['OFFER_CATEGORIES'];
        foreach (array_values($selectOptions) as $optVal) {
            $selected = ((string) $selectedValue === (string) $optVal) ? ' selected' : '';
            echo '<option value="' . $optVal . '"' . $selected . '>' . escape($optVal) . '</option>' . "\n";
        }
        echo '</select>' . "\n";

        echo '<textarea class="' . (isset($errorFields['usage']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" name="usage" rows="6" cols="40">';
        $value = $offer->usage;
        echo escape($value);
        echo '</textarea>' . "\n";
?>

                        </div>
                        <div class="makeAnOfferInfo">
                            <p>Please describe the use (the "Use") you wish to make of the song.
                                Please choose one of the following options and then type a brief description of your plans,
                                including a description of the work (the "Work").
                                Please note that your choice and description of your Use and Work are important because we
                                only license the song to you for a single Use in a particular Work.
                                Additional Works require new licenses.
                            </p>
                        </div>
                        <div class="clear"></div>
                    </div>



                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferLeft">
                            <p>Your Offer</p>
                        </div>
                        <div class="makeAnOfferRight">

<?php
        echo '<input class="' . (isset($errorFields['price']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="text" name="price" maxlength="255" size="40"';
        echo ' value="';
        $value = $offer->price;
        echo (escape($value));
        echo '">' . "\n";

?>

                        </div>
                        <div class="makeAnOfferInfo">
                            <p>What price are you offering $</p>
                        </div>
                        <div class="clear"></div>
                    </div>



                     <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferBroad">
                            <p>These are the terms pursuant to which oneloudr.com ("oneloudr" or "we") licenses the Song to you.
                               You must pay the required fees and comply with all terms, conditions, notices and disclaimers set out on the following screens ("Terms").
                               If you agree with what you read, check the "I have read and understand the license agreement" checkbox below. If you do not agree with what you read below,
                               then click the "I DO NOT ACCEPT" link and do not proceed any further.
                             </p>
                        </div>
                        <div class="clear"></div>
                    </div>

                    <div class="makeAnOfferWrapper">
                        <div class="makeAnOfferBroad">
             <p>
                  <textarea readonly="true" rows="10" cols="140">
oneloudr Commercial Licensee Terms

This agreement ("Agreement") sets forth the terms under which oneloudr grants you ("Licensee") a license to make commercial use of a song posted on the oneloudr service. For good and valuable consideration, the receipt of which is acknowledged, the parties agree as follows.

1. Definitions

Artist: the author or authors of the Song or other party that currently owns the rights to the Song licensed in this Agreement.

License Purchase Terms: the commercial terms completed and executed by Licensee in connection with this Agreement that describe the Song, the Use, the Work, and the fee to be paid for it (and which are hereby incorporated by reference).

Media: any recording or distribution medium, now existing or later developed in any format or version.

Song: the musical composition created by Artist and embodied in a sound recording that (a) was created by Artist; (b) posted on the oneloudr Service; and (c) licensed pursuant to this Agreement as identified in the License Purchase Terms.

Use: the use that Licensee will make of the Song as selected and described by Licensee in the License Purchase Terms.

Work: the audiovisual work, radio commercial, or other work into which the Song will be synchronized or otherwise incorporated as set forth by Licensee in the License Purchase Terms.

2. License

a. Non-exclusive License. Subject to the terms and conditions of this Agreement, oneloudr grants Licensee a world-wide, non-exclusive indefinite term license in all Media to make the Use of the Song that Licensee has described in the License Purchase Terms. oneloudr further grants Licensee the right, in accordance with the terms of this Agreement, to reproduce, distribute, and digitally transmit the Song as needed to make the Use and create and distribute the Work specified in the License Purchase Terms.

b. Limitations. The license granted pursuant to Section 2(b) is limited to the Use described in the License Purchase Terms. As set forth in the License Purchase Terms, the Use described in the License Purchase Terms is intended to include the use of the song in only one Work. Additional works or new works based upon the original Work require the purchase of additional licenses.

c. Attribution. When reasonably practicable, Licensee will provide with the Work the name of the Song, the Artist and the fact that the song was licensed from oneloudr. For the avoidance of doubt and without limitation, this obligation applies to Works that are audiovisual works where such credit is traditionally given, including, but not limited to, films, episodes of television shows, and video games, but does not apply to television and radio commercials.

d. Further Obligation. Licensee will deliver 3 copies of the Work (or a single, digital copy in a non copy-protected format) within 30 days of its first commercial use or distribution.

e. Transferability of License. oneloudr grants Licensee a license, in accordance with the terms of this Agreement, to sublicense the rights granted in this Section solely in connection with the creation of the Work and as necessary to make the Use. The licenses granted herein are not otherwise transferable except in connection with the assignment of this Agreement as set forth in Section 9.

3. Licensing Fees and Payment

a. Licensing Fees. Licensee will pay oneloudr the fees set forth in the License Purchase Terms or as otherwise agreed by the parties pursuant to the "Make an Offer" option. Unless otherwise agreed in writing, Licensee will make payment in accordance with, and using the methods set forth in, the License Purchase Terms.

b. Payment Terms. Any late payment will accrue interest at the lesser of the U.S. Prime Rate of interest plus 3% per month or the maximum interest allowable under applicable law. If Licensee fails to make payment, Licensee will be responsible for all reasonable expenses (including attorneys' fees) incurred by oneloudr in collecting such amounts. All payments due hereunder are in U.S. dollars and are exclusive of any applicable taxes, for which Licensee shall be responsible.

4. Ownership

a. Intellectual Property Rights. All intellectual property rights (including, but not limited to, copyrights, trademarks, and rights of publicity) in or related to the Song are and will remain the exclusive property of oneloudr or the Artist, whether or not specifically recognized or perfected under law. Licensee will not take any action that jeopardizes oneloudr’s or the Artist's proprietary rights, or acquire any right in the Song, except the limited rights specified in this Agreement.

b. Use. Licensee may use oneloudr's trademarks and the Artist's trademarks, identity and persona exclusively in connection with the advertisement and promotion of the Work and the attribution of the Song. From time to time and upon request of oneloudr, Licensee will deliver representative samples of any marketing or promotional materials created by or for Licensee that bear a oneloudr trademark.

5. NO CONSEQUENTIAL DAMAGES; LIMITATION ON LIABILITY

UNDER NO CIRCUMSTANCES WILL ONELOUDR BE LIABLE FOR CONSEQUENTIAL, INDIRECT, SPECIAL, PUNITIVE OR INCIDENTAL DAMAGES OR LOST PROFITS, WHETHER FORESEEABLE OR UNFORESEEABLE, BASED ON CLAIMS ARISING OUT OF BREACH OR FAILURE OF EXPRESS OR IMPLIED WARRANTY, BREACH OF CONTRACT, MISREPRESENTATION, NEGLIGENCE, STRICT LIABILITY IN TORT OR OTHERWISE. IN NO EVENT WILL THE AGGREGATE LIABILITY THAT ONELOUDR MAY INCUR IN ANY ACTION OR PROCEEDING EXCEED THE TOTAL AMOUNT OF LICENSING FEES PAID BY LICENSEE TO ONELOUDR FOR THE SONG. THE LIMITATIONS, EXCLUSIONS AND DISCLAIMERS SET FORTH IN THIS SECTION 5WILL NOT APPLY ONLY IF AND TO THE EXTENT THAT THE LAW OR A COURT OF COMPETENT JURISDICTION REQUIRES LIABILITY UNDER APPLICABLE LAW BEYOND AND DESPITE THESE LIMITATIONS, EXCLUSIONS AND DISCLAIMERS.

6. DISCLAIMER

THE SONG IS PROVIDED "AS IS" WITHOUT WARRANTY OF ANY KIND, EITHER EXPRESS OR IMPLIED. ALL WARRANTIES, CONDITIONS, REPRESENTATIONS, INDEMNITIES AND GUARANTEES WITH RESPECT TO THE SONG, WHETHER EXPRESS OR IMPLIED, ARISING BY LAW, CUSTOM, PRIOR ORAL OR WRITTEN STATEMENTS BY ONELOUDR, OR OTHERWISE (INCLUDING, BUT NOT LIMITED TO ANY WARRANTY OF SATISFACTORY QUALITY, MERCHANTABILITY, FITNESS FOR PARTICULAR PURPOSE, TITLE AND NON-INFRINGEMENT) ARE, TO THE MAXIMUM EXTENT ALLOWED BY LAW, HEREBY OVERRIDDEN, EXCLUDED AND DISCLAIMED.

7. INDEPENDENT PARTIES

Licensee and oneloudr are independent parties. Nothing in this Agreement will be construed to make either party an agent, employee, franchisee, joint venturer or legal representative of the other party. Except as otherwise provided in this Agreement, neither party will have nor represent itself to have any authority to bind the other party or act on its behalf.

8. NOTICES

All notices and requests in connection with this Agreement shall be deemed given as of the day they are received either by receipted, nationwide overnight delivery service, or in the U.S. mails, postage prepaid, certified or registered, return receipt requested, to the address specified in the License Purchase Terms to the attention of the Licensee representative and to the attention of the oneloudr representative designated in this Agreement or to any other address that may be designated by prior notice.

9. ASSIGNMENT

Licensee may assign this Agreement or any of its rights or obligations, upon notice to oneloudr, to its parent or any affiliated company or to any other company or entity pursuant to a merger, acquisition, sale corporate reorganization or other similar transaction. oneloudr may assign, delegate, sub-contract or transfer this Agreement or any of its rights or obligations hereunder. Licensee acknowledges that the provisions of this Agreement are intended to inure to the benefit of the Artist as a third party beneficiary of this Agreement, and the Artist will be entitled to enforce such provisions against Licensee. Licensee further acknowledges that the Artist accepts its third party beneficiary rights hereunder and that such rights will be deemed irrevocable.

10. ARBITRATION

Any controversy or claim arising out of or relating to this contract, or the breach thereof, shall be settled by arbitration administered by the American Arbitration Association under its Commercial Arbitration Rules, and judgment on the award rendered by the arbitrator(s) may be entered in any court having jurisdiction thereof. To initiate arbitration, either party will file the appropriate notice at the Regional Office of the AAA in Nashville, Tennessee, U.S.A. The arbitration proceeding will take place AAA in Nashville, Tennessee, U.S.A. The arbitral award will be the exclusive remedy of the parties for all claims, counterclaims, issues or accountings presented or plead to the arbitrators. Any additional costs, fees or expenses incurred in enforcing the arbitral award will be charged against the party that resists its enforcement. Nothing in this Section will prevent the parties from seeking interim injunctive relief against one another.

11. GENERAL

This Agreement will be governed by and interpreted in accordance with the laws of Tennessee, excluding its conflict of law principles. The parties hereby submit to the jurisdiction of the state or federal courts located in Nashville, Tennessee, waiving any objection to forum non conveniens. This Agreement constitutes the complete and entire statement of all terms, conditions and representations of the agreement between Artist and oneloudr with respect to its subject matter and supersedes all prior writings or understanding. Except as otherwise provided above, any waiver, amendment or other modification of this Agreement will not be effective unless in writing and signed by the party against whom enforcement is sought. If any provision of this Agreement is held to be unenforceable, in whole or in part, such holding will not affect the validity of the other provisions of this Agreement. Sections 4 through 11 of this Agreement will survive any expiration or termination of this Agreement. No provision of this Agreement, nor any ambiguities that may be contained herein, shall be construed against any party on the ground that such party or its counsel drafted the provision at issue or that the provision at issue contains a covenant, representation or warranty of such party. All rights and remedies of the parties set forth in this Agreement shall be cumulative, and none shall exclude any other right or remedy allowed by applicable law.

                </textarea></p>
                 </div>
               <div class="clear"></div>
            </div>

            <div class="makeAnOfferWrapper">
                <div class="makeAnOfferLeft">
                  I have read and understand the license agreement
                </div>
                <div class="makeAnOfferRight">

<?php       echo '<input class="' . (isset($errorFields['accepted']) ? 'inputTextFieldWithProblem' : 'inputTextField') . '" type="checkbox" name="accepted" value="yes">';

            if (isset($errorFields['accepted'])) {
                echo '<p class="problemMessage">' .$errorFields['accepted'] . '</p>';
            }

?>


        </div>
            <div class="clear"></div>
            </div>

            <div class="makeAnOfferWrapper">
                  <div class="makeAnOfferLeft">
                     <input class="button" type="submit" value="send my offer">
                  </div>
                  <div class="makeAnOfferRight">
                    <a href="startPage.php" class="button">I DO NOT ACCEPT</a>
                  </div>
                  <div class="clear"></div>
            </div>


               </div>

               </form>
             </div> <!-- container -->
         </div> <!-- trackFormDiv -->

      <div id="trackFormDivEnd"></div>

<?php

} // end if (!$offerSent)
else {
    echo 'Thank you for making an offer. We will get back to you as soon as possible!';
    echo '<p>&nbsp;</p>';
}

?>

    </div> <!-- pageMainContent -->

    <? include ("footer.php"); ?>

    </div> <!-- bodyWrapper -->

    <?php writeGoogleAnalyticsStuff(); ?>
  </body>
</html>
<?php

function inputDataOk(&$errorFields, &$offer) {
    global $logger;
    $result = true;

    $offer->usage = get_param('usage');
    $offer->price = get_param('price');
    $offer->accepted = get_param('accepted');
    $offer->email = get_param('email');
    $offer->usageCategory = get_param('usageCategory');

    if (!$offer->email) {
        $result = false;
        $errorFields['email'] = 'Please specify your email address!';
    }
    if (!$offer->usage || $offer->usageCategory === '- Please choose -') {
        $result = false;
        $errorFields['usage'] = 'Please describe the use you wish to make of the song and select an usage from the list!';
    }
    if (!$offer->price) {
        $result = false;
        $errorFields['price'] = 'Please provide a price!';
    }
    if (!$offer->accepted) {
        $result = false;
        $errorFields['accepted'] = 'Please accept the terms!';
    }

    return $result;

}

function inputDataOkForArtistOffer(&$errorFields, &$offer) {
    global $logger;
    $result = true;

    $offer->usageArtistOffer = get_param('usageArtistOffer');
    $offer->emailArtistOffer = get_param('emailArtistOffer');
    $offer->usageCategoryArtistOffer = get_param('usageCategoryArtistOffer');

    if (!$offer->emailArtistOffer) {
        $result = false;
        $errorFields['emailArtistOffer'] = 'Please specify your email address!';
    }
    if (!$offer->usageArtistOffer || $offer->usageCategoryArtistOffer === '- Please choose -') {
        $result = false;
        $errorFields['usageArtistOffer'] = 'Please describe the use you wish to make of the song and select an usage from the list!';
    }

    return $result;

}

?>
