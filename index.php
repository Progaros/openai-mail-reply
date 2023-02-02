<?php ///////////
///// SETUP /////
/////////////////

// Once: set nonce cookie
if (!isset($_COOKIE['nonce'])) {
    $nonce = substr(hash('sha256', openssl_random_pseudo_bytes(16)), 0, 16);
    setcookie('nonce', $nonce, time() + (86400 * 365), '/');
    header('Location: .');
    exit;
}
// Once: store key in user cookie
elseif (!isset($_COOKIE['openai']) && isset($_POST['openai'])) {
    // sanitize input
    $rawKey = preg_replace('/[^\-a-zA-Z0-9]/', '', $_POST['openai']);
    // check if key is valid (51 chars long and starts with sk-)
    if (strlen($rawKey) == 51 && substr($rawKey, 0, 3) == 'sk-') {
        $secret = hash('sha256', filemtime("./index.php"));
        $key = openssl_encrypt($rawKey, 'aes-256-cbc', $secret, 0, $_COOKIE['nonce']); // encrypt key
        setcookie('openai', $key, time() + (86400 * 365), '/');
        header('Location: .');
        exit;
    } else {
        echo '<p class="secondary-text">Invalid key: ' . $rawKey . '</p>';
        exit;
    }
    exit;
}
// Always: decrypt key from cookie
elseif (isset($_COOKIE['openai'])) {
    $secret = hash('sha256', filemtime("./index.php"));
    $key = openssl_decrypt($_COOKIE['openai'], 'aes-256-cbc', $secret, 0, $_COOKIE['nonce']);
    if (strlen($key) != 51 || substr($key, 0, 3) != 'sk-') {
        // remove invalid key
        setcookie('openai', '', time() - 3600, '/');
        header('Location: .');
        exit;
    }
} ///////////////
///// SETUP /////
/////////////////

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reply</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 0 auto;
            margin-top: 50px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1.2rem;
            text-align: center;
        }

        button {
            border-radius: 5px;
            font-size: 1.2rem;
            border: 1px solid;
        }

        a {
            text-decoration: none;
            color: #0058f7;
        }

        input {
            font-size: 1.2rem;
            border-radius: 5px;
            padding: 5px;
            border: 1px solid;
        }

        textarea {
            padding: 10px;
            border-radius: 5px;
            resize: none;
            border: 1px solid;
        }

        input[type="radio"] {
            position: relative;
            opacity: 0;
            width: 1px;
            margin: -3px;
            border: none;
            left: 3rem;
        }

        label {
            margin: 10px;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 2rem;
            background: transparent;
            transition: all 0.3s ease-in-out;
            border-radius: 5px;
        }

        label:hover,
        input[type="radio"]:checked+label {
            background: #9e9e9e;
        }

        input[type="submit"] {
            padding: 5px 10px;
            cursor: pointer;
            border: 1px solid;
        }

        #customText,
        #name {
            margin: 0 auto;
            margin-top: 20px;
            display: none;
        }

        .response {
            margin: 0 auto;
            font-size: 1.2rem;
            white-space: pre-line;
            text-align: justify;
            padding: 0 20% 50px 20%;
        }

        .copy {
            width: 5.4rem;
            transition: background-color 0.3s ease-in-out;
        }

        .subheading {
            margin-top: -20px
        }

        /* Color Scheme */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #1e1e1e;
                color: #fff;
            }

            button,
            input[type="submit"] {
                background: #9e9e9e;
                color: black;
                border: none;
            }

            textarea,
            input[type="text"],
            input[type="password"] {
                background-color: #1e1e1e;
                color: #fff;
                border: 1px solid #fff;
            }

            .secondary-text {
                color: #aaa;
            }

            .loading__dot {
                background-color: #fff;
            }
        }

        @media (prefers-color-scheme: light) {
            body {
                background-color: #fff;
                color: #000;
            }

            .loading__dot {
                background-color: #000;
            }

            .secondary-text {
                color: #555;
            }
        }

        /* Color Scheme */

        /* Loading animation */
        .loading {
            margin-top: 30px;
            display: none;
        }

        .loading__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin: 0 5px;
            animation: loader 1s ease-in-out infinite;
        }

        .loading__dot:nth-child(2) {
            animation-delay: 0.1s;
        }

        .loading__dot:nth-child(3) {
            animation-delay: 0.2s;
        }

        .loading__dot:nth-child(4) {
            animation-delay: 0.3s;
        }

        .loading__dot:nth-child(5) {
            animation-delay: 0.4s;
        }

        .loading__dot:nth-child(6) {
            animation-delay: 0.5s;
        }

        @keyframes loader {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1);
            }

            100% {
                transform: scale(0);
            }
        }

        /* Loading animation */
    </style>
</head>

<body>
    <h1>Reply</h1>

    <?php ///////////////////////
    ///// show api key form /////
    /////////////////////////////
    if (!isset($_COOKIE['openai']) && !isset($_POST['openai'])) : ?>

        <h3 class="subheading">AI generated email replies</h3>

        <p>Please enter your <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAI API key</a> below<br>
            (it will be stored encrypted in a cookie on your device)</p>

        <form action="." method="post">
            <input type="password" name="openai" id="openai" placeholder="OpenAI API key...">
            <input type="submit" value="Submit" style="margin-top: 10px;">
        </form>
        <script>
            // check if key is valid
            const openai = document.getElementById('openai');
            const submit = document.querySelector('input[type="submit"]');
            openai.setCustomValidity('Please enter OpenAI API key');
            openai.addEventListener('input', () => {
                if (openai.value.length === 51 && openai.value.startsWith('sk-')) {
                    openai.setCustomValidity('');
                    submit.style.backgroundColor = '#8bc34a';
                } else if (openai.value.length === 0) {
                    openai.setCustomValidity('Please enter OpenAI API key');
                } else {
                    openai.setCustomValidity('Invalid OpenAI API key');
                }
            });
        </script>

    <?php //////////////////////////
    ///// show main email form /////
    ////////////////////////////////
    elseif (!isset($_POST['email'])) : ?>
        <form action="." method="post" id="emailForm">
            <textarea name="email" id="" cols="30" rows="10" placeholder="Paste your email here..." required></textarea><br>

            <div id="choice">
                <p>Choose a response type:</p>
                <input type="radio" name="choice" value="positive" id="positiveChoice" required> <label for="positiveChoice">👍</label>
                <input type="radio" name="choice" value="negative" id="negativeChoice" required> <label for="negativeChoice">👎</label>
                <input type="radio" name="choice" value="custom" id="customChoice" required> <label for="customChoice">📝</label>
                <input type="text" name="custom" id="customText" placeholder="Short additional information...">
                <input type="text" name="name" id="name" placeholder="Your name (sender name)...">
            </div><br>
            <input type="submit" value="Reply">
        </form>
        <div class="loading">
            <div class="loading__dot"></div>
            <div class="loading__dot"></div>
            <div class="loading__dot"></div>
            <div class="loading__dot"></div>
            <div class="loading__dot"></div>
            <div class="loading__dot"></div> <br>
        </div>

        <script>
            // show custom text input if custom choice is selected
            const choiceButtons = document.getElementsByName('choice');
            const customChoice = document.getElementById('customChoice');
            const customText = document.getElementById('customText');
            for (let i = 0; i < choiceButtons.length; i++) {
                choiceButtons[i].addEventListener('change', () => {
                    if (customChoice.checked) {
                        customText.style.display = 'block';
                        customText.setAttribute('required', '');
                    } else {
                        customText.style.display = 'none';
                        customText.removeAttribute('required');
                    }
                });
            }

            // check for missing surname in beginning of email
            const email = document.querySelector('textarea');
            if (email) {
                email.addEventListener('change', () => {
                    const firstLine = email.value.split('\n')[0].toLowerCase();
                    console.log(firstLine);
                    if (firstLine.includes('mr') || firstLine.includes('mrs') || firstLine.includes('ms') || firstLine.includes('herr') || firstLine.includes('frau')) {
                        document.querySelector('#name').style.display = 'block';
                    } else {
                        document.querySelector('#name').style.display = 'none';
                    }
                });
            }

            // on submit show loader and hide form
            const form = document.querySelector('#emailForm');
            const loader = document.querySelector('.loading');
            const headline = document.querySelector('h1');
            if (form) {
                form.addEventListener('submit', () => {
                    loader.style.display = 'flex';
                    form.style.display = 'none';
                    headline.textContent = 'Waiting for reply...';
                });
            }
        </script>

    <?php  //////////////////////////
    ///// get reply and show it /////
    /////////////////////////////////
    else : ?>
        <?php
        // handle name and custom choice
        if (!empty($_POST['name'])) {
            $_POST['name'] =  'as ' . $_POST['name'] . ' ';
        }
        if ($_POST['choice'] === 'custom') {
            if (empty($_POST['custom'])) {
                echo '<p class="response__text">Please enter a custom choice</p>';
                echo '<script>document.querySelector("body").innerHTML += "<button onclick=\'history.back()\'>Go back</button>";</script>';
                exit;
            }
            $_POST['custom'] = 'with this content: "' . $_POST['custom'] . '"';
        } else {
            $_POST['custom'] = '';
        }

        // send request to OpenAI API
        $url = 'https://api.openai.com/v1/completions';
        $headers = array('Content-Type: application/json', 'Authorization: Bearer ' . $key);
        $data = json_encode(array(
            'model' => 'text-davinci-003', // positive, negative, custom  // with this content: "custom text"
            'prompt' => 'Write a response' . $_POST['choice'] . 'reply ' . $_POST['custom'] . $_POST['name'] . ' to this email I got (formatted with greeting and regards and newlines in between): ' . $_POST['email'],
            'temperature' => 0.7,
            'max_tokens' => 256,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ));
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $data,
            ),
        );
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);

        // handle response
        if ($response === false) { // error
            echo 'Error, redirecting...';
            // delete cookies using JS & redirect
            echo '<script>document.cookie.split(";").forEach(function(c) { document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/"); });</script>';
            echo '<script>setTimeout(() => {window.location.href = "."}, 2000);</script>';
            exit;
        } else { // success
            echo '<div class="response">';
            echo json_decode($response, true)['choices'][0]['text']; // print response
            echo '</div>';

            $cost = (floatval(json_decode($response, true)['usage']['total_tokens'])  / 1000 * 2);
            echo '<div class="secondary-text">';
            echo "Cost: around " . $cost . "ct";
            echo '</div>';
        }
        ?>
        <br>
        <button onclick="history.back()">Go back</button>
        <br>
        <button onclick="copy()" class="copy">Copy</button>
        </div>

        <script>
            // copy response to clipboard (plain text)
            function copy() {
                var response = document.querySelector('.response').innerText;
                navigator.clipboard.writeText(response);

                const copyButton = document.querySelector('.copy');
                copyButton.textContent = 'Copied!';
                var color = getComputedStyle(copyButton).backgroundColor;
                console.log(color);
                copyButton.style.backgroundColor = '#8bc34a';
                setTimeout(() => {
                    copyButton.style.backgroundColor = color;
                    copyButton.textContent = 'Copy';
                }, 1000);
            }
        </script>

    <?php endif; ?>

    <script>
        // dark theme based on system preference
        const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
    </script>
</body>

</html>
