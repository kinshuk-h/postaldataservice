<!doctype html>
<html>
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
        <title>Postal Data Service</title>
        <style>
            .p-large {
                padding-top: 2rem;
                padding-bottom: 2rem;
            }
            .hidden { display: none; }
            .clickable { cursor: pointer; }
            .vertical-table-container {
                overflow-y: auto;
                max-height: 10rem;
            }
            .limited {
                display: block;
                max-height: 18rem;
                overflow-y: auto;
            }
            .is-maxwidth thead, .is-maxwidth tbody tr {
                display: table;
                width: 100%;
                table-layout: fixed;
            }
        </style>
    </head>
    <body class="container is-fluid">
        <?php
            require_once "logger.php";
            require_once "database.php";

            class RequestException extends Exception {}

            function makeNotification($text, $class='is-info is-light') {
                echo "<p class='notification $class'>$text</p>";
            }

            $postal_data = new PostalDatabaseConnection();
            $logger = new FileLogger("./error_log.txt");

            $pincode = $_REQUEST["pincode"] ?? '';
        ?>
        <article class="hero">
            <section class="hero-head">
                <div class="container p-large">
                    <h2 class="title">Postal Data Service</h2>
                    <h6 class="subitle">Enter a PIN Code to get Post Office details.</h6>
                </div>
            </section>
        </article>
        <article class="container block">
            <form class="box" method="POST">
                <div class="field is-horizontal">
                    <div class="field-label is-normal">
                        <label class="label">Postal Code</label>
                    </div>
                    <div class="field-body">
                        <div class="field">
                            <p class="content is-expanded">
                                <input class="input" name="pincode" type="number"
                                        value="<?= $pincode ?>"
                                        placeholder="Enter a 6-digit postal code"
                                        min="100000" max="999999">
                            </p>
                        </div>
                    </div>
                </div>
                <div class='field is-horizontal'>
                    <div class='field-body'>
                        <div class='field is-grouped is-grouped-right'>
                            <div class='control'>
                                <input type='submit' value='Search' class='button is-info' >
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </article>
        <article class="container block">
            <?php
                if(isset($_REQUEST["pincode"])) {
                    if(!$pincode)
                        makeNotification("No pincode specified. Specify one to load data.", "is-danger is-light");
                    else {
                        try {
                            $pincode_data = $postal_data->get_pincode($pincode);
                            if($pincode_data === FALSE) {
                                $response = file_get_contents("https://api.postalpincode.in/pincode/$pincode");
                                $data = json_decode($response, true)[0];
                                if($data["Status"] == "Success") {
                                    $post_offices = $data["PostOffice"];
                                    $pincode_data = $post_offices[0];
                                    $postal_data->save_pincode($pincode, $pincode_data);
                                    $postal_data->save_post_offices($pincode, $post_offices);
                                }
                                else throw new RequestException($data["Status"].": ".$data["Message"]);
                            }
                            else $post_offices = $postal_data->get_post_offices($pincode);
                            echo <<<HTML
                                <section class="box">
                                    <p class="subtitle">
                                        Location Details for the Postal Index Number Code $pincode:
                                    </p>
                                    <section class="table-container">
                                        <table class="table is-fullwidth">
                                            <thead>
                                                <tr>
                                                    <th>District</th>
                                                    <th>Division</th>
                                                    <th>Region</th>
                                                    <th>Block</th>
                                                    <th>Circle</th>
                                                    <th>State</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>{$pincode_data["District"]}</td>
                                                    <td>{$pincode_data["Division"]}</td>
                                                    <td>{$pincode_data["Region"]}</td>
                                                    <td>{$pincode_data["Block"]}</td>
                                                    <td>{$pincode_data["Circle"]}</td>
                                                    <td>{$pincode_data["State"]}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </section>
                                </section>
                            HTML;
                        }
                        catch(RequestException $e) {
                            makeNotification($e->getMessage(), "is-danger is-light");
                        }
                        catch(Throwable $e) {
                            $logger->error($e);
                            makeNotification(
                                "An internal error occurred. Try again, and if the issue ".
                                "persists, contact the developer.", "is-danger is-light"
                            );
                        }
                    }
                }
            ?>
        </article>
        <article class="container block">
            <?php if(!isset($post_offices)) $post_offices = []; ?>
            <section class="box <?= count($post_offices) > 0 ? "" : "hidden" ?>">
                <p class="subtitle">
                    List of Post Offices under the Postal Index Number Code <?= $pincode ?>:
                </p>
                <table class="table is-hoverable is-striped is-scrollable is-maxwidth">
                    <thead>
                        <tr>
                            <th>Post Office Name</th>
                            <th>Branch Type</th>
                        </tr>
                    </thead>
                    <tbody class="limited">
                        <?php
                            foreach($post_offices as $office) {
                                echo <<<HTML
                                    <tr>
                                        <td>{$office["Name"]}</td>
                                        <td>{$office["BranchType"]}</td>
                                    </tr>
                                HTML;
                            }
                        ?>
                    </tbody>
                </table>
            </section>
        </article>
        <article class="container block">
            <?php
                $old_pincodes = $postal_data->get_pincodes();
                echo "<pre>".var_dump($old_pincodes)."</pre>";
            ?>
            <section class="box <?= count($old_pincodes) > 0 ? "" : "hidden" ?>">
                <h4 class="subtitle">
                    List of Postal Codes previously recorded:
                </h4>
                <section class="table-container">
                    <table class="table is-hoverable is-fullwidth is-striped">
                        <thead>
                            <tr>
                                <th>PIN Code</th>
                                <th>District</th>
                                <th>Division</th>
                                <th>Region</th>
                                <th>Block</th>
                                <th>Circle</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach($old_pincodes as $pincode) {
                                    echo <<<HTML
                                        <tr class="clickable" onclick='postForm({$pincode["Pincode"]})'>
                                            <td>{$pincode["Pincode"]}</td>
                                            <td>{$pincode["District"]}</td>
                                            <td>{$pincode["Division"]}</td>
                                            <td>{$pincode["Region"]}</td>
                                            <td>{$pincode["Block"]}</td>
                                            <td>{$pincode["Circle"]}</td>
                                            <td>{$pincode["State"]}</td>
                                        </tr>
                                    HTML;
                                }
                            ?>
                        </tbody>
                    </table>
                </section>
            </section>
        </article>
        <footer class="block"></footer>
        <script>
            function postForm(pincode) {
                let form = document.forms[0];
                form.pincode.value = pincode;
                form.submit();
            }
        </script>
    </body>
</html>