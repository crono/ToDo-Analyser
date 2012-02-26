<?php
// vim: set ts=4 et nu shiftwidth=4 :vim
//
interface calculable {
    public function value();
}

interface Comparable {
    public function compare(self $compare);
}


function sectostr($duration,$incsec=true) {

    return sprintf('%02.2f',$duration/3600);
    $hour=(int)($duration/3600);
    $min=(int)(($duration % 3600) / 60);
    $sec=(int)($duration % 60);
    if ($incsec) return sprintf('%02d:%02d:%02d',$hour,$min,$sec);
    return sprintf('%02d:%02d',$hour,$min);
}

# From: http://code.google.com/p/php-calendar/source/browse/trunk/php-calendar/includes/calendar.php
function day_of_week_start()
{
        global $phpcid;

        return get_config($phpcid, 'week_start');
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week($month, $day, $year)
{
        return day_of_week_ts(mktime(0, 0, 0, $month, $day, $year));
}

// returns the number of days in the week before the 
//  taking into account whether we start on sunday or monday
function day_of_week_ts($timestamp)
{
        $days = date('w', $timestamp);

        return ($days + 7 - day_of_week_start()) % 7;
}

// returns the number of days in $month
function days_in_month($month, $year)
{
#    print date('t', mktime(0, 0, 0, $month, 1, $year)).'.'.date('L', mktime(0, 0, 0, $month, 1, $year)).' ';
        return date('t', mktime(0, 0, 0, $month, 1, $year));
}

//returns the number of weeks in $month
function weeks_in_month($month, $year)
{
        $days = days_in_month($month, $year);

        // days not in this month in the partial weeks
        $days_before_month = day_of_week($month, 1, $year);
        $days_after_month = 6 - day_of_week($month, $days, $year);

        // add up the days in the month and the outliers in the partial weeks
        // divide by 7 for the weeks in the month
        return ($days_before_month + $days + $days_after_month) / 7;
}

// return the week number corresponding to the $day.
function week_of_year($month, $day, $year)
{
        global $phpcid;

        $timestamp = mktime(0, 0, 0, $month, $day, $year);

        // week_start = 1 uses ISO 8601 and contains the Jan 4th,
        //   Most other places the first week contains Jan 1st
        //   There are a few outliers that start weeks on Monday and use
        //   Jan 1st for the first week. We'll ignore them for now.
        if(get_config($phpcid, 'week_start') == 1) {
                $year_contains = 4;
                // if the week is in December and contains Jan 4th, it's a week
                // from next year
                if($month == 12 && $day - 24 >= $year_contains) {
                        $year++;
                        $month = 1;
                        $day -= 31;
                }
        } else {
                $year_contains = 1;
        }
        
        // $day is the first day of the week relative to the current month,
        // so it can be negative. If it's in the previous year, we want to use
        // that negative value, unless the week is also in the previous year,
        // then we want to switch to using that year.
        if($day < 1 && $month == 1 && $day > $year_contains - 7) {
                $day_of_year = $day - 1;
        } else {
                $day_of_year = date('z', $timestamp);
                $year = date('Y', $timestamp);
        }

        /* Days in the week before Jan 1. */
        $days_before_year = day_of_week(1, $year_contains, $year);

        // Days left in the week
        $days_left = 8 - day_of_week_ts($timestamp) - $year_contains;

        /* find the number of weeks by adding the days in the week before
         * the start of the year, days up to $day, and the days left in
         * this week, then divide by 7 */
        return ($days_before_year + $day_of_year + $days_left) / 7;
}


?>
