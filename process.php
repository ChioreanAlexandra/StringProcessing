<?php
/**
 * @param string $inputString
 */
function mainApp(string $inputString)
{
    $generalInfoArray = explode('|', $inputString);
    $activityName = ["Cod activitate", "Nume activitate", "Ora", "Rata orara", "Suma primita"];
    $name = getName($generalInfoArray[0], $generalInfoArray[1]);


    $activityArray = activityInfo($generalInfoArray[3]);
    $activityArray = computeSumPerActivity($activityArray);
    //$activityArray = computeTotalSum($activityArray);
    $activityArrayWithFormat = setMoneyFormat($activityArray);

    $totalSum = computeTotalSum($activityArray);


    // printTotal($totalSum);
    $totalContributions = 0;
    $contributionArray = processContributions($generalInfoArray[4], $totalSum, $totalContributions);

    printName($name);
    printCNP($generalInfoArray[2]);
    printArray($activityName);
    displayFormatArray($activityArray);
    printLine();
    printTotal($totalSum);
    printContributions($contributionArray);
    printTotalAfterApplyingTaxes($totalSum, $totalContributions);
}

/**
 * @param string $firstName
 * @param string $lastName
 * @return string
 */
function getName(string $firstName, string $lastName): string
{
    $firstName = str_replace('+', ' ', $firstName);
    $firstName = ucwords($firstName);
    $lastName = mb_convert_case($lastName, MB_CASE_UPPER, "UTF-8");
    return $firstName . " " . $lastName;
}

/**
 * @param string $infos
 * @return array
 */
function extractHours(string $infos): float
{
    preg_match('/(?<hours>\d+)h(?<minutes>\d*)m?/', $infos, $matches);
    $min = (float)$matches['minutes'];
    $hours = (float)$matches['hours'] + ($min / 60);
    return $hours;
}

function extractRate(string $infos): float
{
    preg_match('/\*(?<group>\d+\.\d+)\/h/', $infos, $match);
    var_dump($infos, $match['group']);
    return (float)$match['group'];
}

/*function parseActivity(string $infos): array
{
    $infos = trim($infos, '[]');
    $arrayOfInfo = explode(';', $infos);
    $activityData = explode('*', $arrayOfInfo[2]);

    unset($arrayOfInfo[2]);

    /*$indexH = strpos($activityData[0], 'h');
    $hours = (float)substr($activityData[0], 0, $indexH);
    $indexM = (int)strpos($activityData[0], 'm');
    $min = (float)substr($activityData[0], $indexH + 1, $indexM - $indexH + 1);
    $hours += $min / 60;
    $arrayOfInfo[2] = $hours;
    $index_s = (int)strpos($activityData[1], '/');
    $ratePerHour = (float)substr($activityData[1], 0, $index_s);
    $arrayOfInfo[3] = $ratePerHour;
    //$sum = $hours * $ratePerHour;
   // $arrayOfInfo[4] = $sum;
    return $arrayOfInfo;
}*/

/**
 * @param string $activities
 * @return float
 */
function parseActivities(array $activity): array
{
    $activityRow = [];
    $activityRow[] = $activity[0];
    $activityRow[] = $activity[1];
    $activityRow[] = extractHours($activity[2]);
    $activityRow[] = extractRate($activity[2]);

    return $activityRow;
}

function activityInfo(string $activities): array
{
    $arrayOfActivities = explode(',', $activities);
    usort($arrayOfActivities, "strnatcmp");
    $activityMatrix = [];
    foreach ($arrayOfActivities as $item) {
        $item = trim($item, '[]');
        $activityRow = explode(';', $item);
        $activityMatrix[] = parseActivities($activityRow);
    }
    return $activityMatrix;
}

function computeSumPerActivity(array $arrayMatrix): array
{
    $activity = [];
    foreach ($arrayMatrix as $arrayItem) {
        $arrayItem[] = $arrayItem[2] * $arrayItem[3];
        $activity[] = $arrayItem;
    }
    return $activity;
}

function computeTotalSum(array $arrayMatrix): float
{
    $total = 0;
    foreach ($arrayMatrix as $arrayItem) {
        $total += $arrayItem[4];
    }
    return $total;
}

function setMoneyFormat(array $arrayMatrix): array
{
    setlocale(LC_MONETARY, 'ro_RO.UTF-8');
    $activityMatrix = [];
    foreach ($arrayMatrix as $arrayItem) {
        $arrayItem[3] = money_format('%.2i', $arrayItem[3]);
        $arrayItem[4] = money_format('%.2i', $arrayItem[4]);
        $activityMatrix[] = $arrayItem;
    }
    return $activityMatrix;
}

/**
 * @param string $contributions
 * @param float $totalSum
 * @param float $contributionsTotal
 * @return array
 */
function getContributionTotal()
{

}

function processContributions(string $contributions, float $totalSum, float &$contributionsTotal): array
{
    setlocale(LC_MONETARY, 'ro_RO.UTF-8');
    $arrayOfContributions = explode('%', $contributions);
    unset($arrayOfContributions[3]);//exploding by % will make the last element empty
    $contributionsInfo = [];
    $contributionsTotal = 0;
    foreach ($arrayOfContributions as $item) {
        $indexDigit = strcspn($item, '0123456789');
        $name = substr($item, 0, $indexDigit);
        $length = mb_strlen($item) - $indexDigit;
        $rate = substr($item, $indexDigit, $length);
        $rate = (float)str_replace(',', '.', $rate);
        $contr = $rate * (float)$totalSum / 100;
        $contributionsTotal += $contr;
        $contr = money_format('%.2i', $contr);
        $contributionsInfo[] = [$name, $rate, $contr];
    }
    return $contributionsInfo;
}


/**
 * @param array $arrayContributions
 */
function printContributions(array $arrayContributions)
{
    echo "CONTRIBUTII" . PHP_EOL;
    printLine();
    foreach ($arrayContributions as $item) {
        echo sprintf('%s|%s%%|%s%s',
            str_pad(mb_strtoupper($item[0]), 20),
            str_pad($item[1], 61, " ", STR_PAD_LEFT),
            str_pad($item[2], 20, " ", STR_PAD_LEFT),
            PHP_EOL);
    }
}

/**
 * @param float $totalSum
 * @param float $contributionTotal
 */
function printTotalAfterApplyingTaxes(float $totalSum, float $contributionTotal)
{
    printLine();
    echo PHP_EOL;
    setlocale(LC_MONETARY, 'ro_RO.UTF-8');
    $salary = $totalSum - $contributionTotal;
    $salaryWithFormat = money_format('%.2i', $salary);
    echo "TOTAL";
    echo str_pad($salaryWithFormat, 99, " ", STR_PAD_LEFT) . PHP_EOL;
}

function printLine()
{
    echo str_repeat('-', 104);
    echo PHP_EOL;
}

/**
 *
 * @param float $totalSum
 */
function printTotal(float $totalSum): void
{
    setlocale(LC_MONETARY, 'ro_RO.UTF-8');
    $total = money_format('%.2i', $totalSum);
    echo "TOTAL BRUT";
    echo str_pad($total, 94, " ", STR_PAD_LEFT) . PHP_EOL;
    echo PHP_EOL;
}

/**
 * @param array $arrayToDisplay
 */
function displayFormatArray(array $arrayMatrix)
{
    foreach ($arrayMatrix as $arrayToDisplay) {
        echo sprintf(
            '%s|%s|%s|%s|%s%s',
            str_pad($arrayToDisplay[0], 20),
            str_pad($arrayToDisplay[1], 20),
            str_pad($arrayToDisplay[2], 20, " ", STR_PAD_LEFT),
            str_pad($arrayToDisplay[3], 20, " ", STR_PAD_LEFT),
            str_pad($arrayToDisplay[4], 20, " ", STR_PAD_LEFT),
            PHP_EOL
        );
    }

}

/**
 * @param string $name
 */
function printName(string $name)
{

    echo sprintf('%s|%s%s', str_pad("Nume", 20),
        str_pad($name, 20),
        PHP_EOL);
}

/**
 * @param string $cnp
 */
function printCNP(string $cnp)
{

    echo sprintf('%s|%s%s%s',
        str_pad("CNP", 20),
        str_pad($cnp, 20),
        PHP_EOL,
        PHP_EOL);
}

/**
 * @param array $arrayToPrint
 */
function printArray(array $arrayToPrint)
{
    $auxiliary = "";
    foreach ($arrayToPrint as $item) {
        $auxiliary .= str_pad($item, 20) . "|";
    }
    $auxiliary[mb_strlen($auxiliary) - 1] = PHP_EOL;
    echo $auxiliary;
}
