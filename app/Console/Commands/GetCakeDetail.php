<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetCakeDetail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee:file {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get detail of cake';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function check_holiday($holiday, $day_off_onholiday = false, $nextyear = false) {
        //holiday on birth so cake come in new year for december enddate employee  
        if($nextyear) {
            if(date('N', strtotime($holiday)) >= 6) {
                $find_date = date('Y-m-d', strtotime($holiday . ' +1 day'));
                $birthday_off = $this->check_holiday($find_date, false, true);
            } else {
                $birthday_off = $holiday;
            }
        } else {
            $array_holiday = [];

            $currentYear = date('Y');
            $next_year = date('Y', strtotime($holiday));
            
            //find the newyear, chrimas and boxing day holiday and add into holiday array
            $holiday_newyear = date('Y-m-d', strtotime("first day of january $currentYear"));
            $holiday_christmas = date('Y-m-d', strtotime("december 25 $currentYear"));
            $holiday_boxing = date('Y-m-d', strtotime("december 26 $currentYear"));
            array_push($array_holiday, $holiday_newyear);
            array_push($array_holiday, $holiday_christmas);
            array_push($array_holiday, $holiday_boxing);

            //holiday on birth so cake come in new year for december enddate employee  
            if($next_year > $currentYear) {
                $nextyear_holiday_newyear = date('Y-m-d', strtotime("first day of january $next_year"));
                array_push($array_holiday, $nextyear_holiday_newyear);
                $nextyear_checkoff_newyear = $this->check_holiday($nextyear_holiday_newyear, false, true);
                if(strtotime($nextyear_checkoff_newyear) != strtotime($nextyear_holiday_newyear) && !in_array($nextyear_checkoff_newyear, $array_holiday)) {
                    array_push($array_holiday, $nextyear_checkoff_newyear);
                }
            }
            
            //if weekend on holiday then find new off date
            if($day_off_onholiday) {
                if(date('N', strtotime($holiday)) >= 6) {
                    $find_date = date('Y-m-d', strtotime($holiday . ' +1 day'));
                    $birthday_off = $this->check_holiday($find_date, true);
                    if(in_array($birthday_off, $array_holiday)) {
                        $find_next_off_date = date('Y-m-d', strtotime($birthday_off . ' +1 day'));
                        $birthday_off = $this->check_holiday($find_next_off_date, true);
                    }
                } else {
                    $birthday_off = $holiday;
                }
            } else {
                if(date('N', strtotime($holiday)) >= 6) {
                    $find_date = date('Y-m-d', strtotime($holiday . ' +1 day'));
                    $birthday_off = $this->check_holiday($find_date);
                } else {
                    //if weekend on holiday then holiday transfer to weekdays so that day off find and add in array 
                    $checkoff_newyear = $this->check_holiday($holiday_newyear, true);
                    $checkoff_christmas = $this->check_holiday($holiday_christmas, true);
                    $checkoff_boxing = $this->check_holiday($holiday_boxing, true);
                    if(strtotime($checkoff_newyear) != strtotime($holiday_newyear) && !in_array($checkoff_newyear, $array_holiday)) {
                        array_push($array_holiday, $checkoff_newyear);
                    }
                    if(strtotime($checkoff_christmas) != strtotime($holiday_christmas) && !in_array($checkoff_christmas, $array_holiday)) {
                        array_push($array_holiday, $checkoff_christmas);
                    }
                    if(strtotime($checkoff_boxing) != strtotime($holiday_boxing) && !in_array($checkoff_boxing, $array_holiday)) {
                        array_push($array_holiday, $checkoff_boxing);
                    }

                    //if weekend then new date else set the birthday off date
                    if(!in_array($holiday, $array_holiday)) {
                        $birthday_off = $holiday;
                    } else {
                        $holiday_date = date('Y-m-d', strtotime($holiday . ' +1 day'));
                        $birthday_off = $this->check_holiday($holiday_date);
                    }
                }
            }
        }
        
        return $birthday_off;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filename = base_path().$this->argument('filename');
        
        $myfile = fopen($filename, "rb");
        
        $wrongFile = false;
        $final_array = array();
        while(!feof($myfile)) {
            //string split by "," and make array
            $name_bday = explode(',', fgets($myfile));

            //remove extra space from all value in array
            $trimmed_array = array_map('trim', $name_bday);

            if(sizeof($name_bday) == 3) {
                //if employee with joining date
                if(!empty($name_bday[0]) && !empty($name_bday[1]) && !empty($name_bday[2])) {
                    $b_day = date('m-d', strtotime($name_bday[1]));
                    $join_day = date('m-d', strtotime($name_bday[2]));
                    if($join_day > $b_day) {
                        if(date('Y',strtotime($name_bday[2])) == date('Y')) {
                            $final_array[] = $name_bday[2];
                        } else {
                            $final_array[] = $name_bday[1];    
                        }
                    } else {
                        $final_array[] = $name_bday[1];
                    }
                } else {
                    $wrongFile = true;
                    break;
                }
            } else if(sizeof($name_bday) == 2) {
                //if employee without joining date
                if(!empty($name_bday[0]) && !empty($name_bday[1])) {
                    $final_array[] = $name_bday[1];
                } else {
                    $wrongFile = true;
                    break;
                }
            } else {
                $wrongFile = true;
                break;
            }
        }
        fclose($myfile);
        
        if($wrongFile) {
            echo "File not proper";
        } else {
            $cake_date_array = [];
            $cake_detail_array = [];

            sort($final_array);
            foreach($final_array as $emp) {
                $cake_array = [];

                //From the birth date make new date which is current year date
                $date = date('d', strtotime($emp));
                $month = date('m', strtotime($emp));
                $new_date = date('Y') . '-' . $month . '-' . $date;
                
                //check the date and if weekend, holiday on that then next day holiday for birthday
                $birthday_off = $this->check_holiday($new_date);

                //Check the date and day for cake celebration
                $check_cake_date = date('Y-m-d', strtotime($birthday_off . ' +1 day'));
                $cake_day = $this->check_holiday($check_cake_date);
 
                if(empty($cake_detail_array) || !in_array($cake_day, $cake_date_array)) {
                    $cake_array['date'] = $cake_day; 
                    $cake_array['s_size'] = 1; 
                    $cake_array['l_size'] = 0;
                    $cake_array['person_count'] = 1; 
                    $cake_date_array[] =  $cake_day;
                    $cake_detail_array[$cake_day] = $cake_array;
                } else {
                    if(in_array($cake_day, $cake_date_array)) {
                        $cake_detail_array[$cake_day]['l_size'] = 1; 
                        $cake_detail_array[$cake_day]['s_size'] = 0; 
                        $cake_detail_array[$cake_day]['person_count'] += 1; 
                    }
                }
            }
            //prepare sorted array of cake to employee on which date and count the employee if same date and cake size
            sort($cake_date_array);
            
            //check if two or more employee cake in row or break health reason
            foreach($cake_date_array as $key => $cake) {
                $next_cake = date('Y-m-d', strtotime($cake . ' +1 day'));
                if(in_array($next_cake, $cake_date_array) && in_array($cake, $cake_date_array)) {
                    //if cake in row for more then 2 people then next cake date change to after one day as per health reason
                    if(isset($cake_detail_array[$next_cake])) {
                        //change the cake date, change size of cake and increase the count of person sharing cake
                        $cake_detail_array[$next_cake]['date'] = $next_cake;
                        $cake_detail_array[$next_cake]['s_size'] = 0; 
                        $cake_detail_array[$next_cake]['l_size'] = 1; 
                        $cake_detail_array[$next_cake]['person_count'] = $cake_detail_array[$next_cake]['person_count'] + $cake_detail_array[$cake]['person_count']; 

                        //unset the cake array and key of which already added to next cake date
                        unset($cake_detail_array[$cake]);
                        unset($cake_date_array[$key]);
                        $new_cakekey = array_search($next_cake, $cake_date_array);
                        unset($cake_date_array[$new_cakekey]);
                        
                        //if two cake in row then health reason change the cake to next working day
                        $check_next_cake = date('Y-m-d', strtotime($next_cake . ' +1 day'));
                        $new_next_cake = $this->check_holiday($check_next_cake);
                        if(isset($cake_detail_array[$new_next_cake])) {
                            //if cake is on 18, 19, 20, 21 then 18, 19 celebrate on 19 and 20, 21 celebrate on 21
                            $check_nexttonext_cake = date('Y-m-d', strtotime($new_next_cake . ' +1 day'));
                            $new_nexttonext_cake = $this->check_holiday($check_nexttonext_cake);
                            if(!isset($cake_detail_array[$new_nexttonext_cake])) {
                                $cake_detail_array[$new_nexttonext_cake] = $cake_detail_array[$new_next_cake];
                                $cake_detail_array[$new_nexttonext_cake]['date'] = $new_nexttonext_cake;
                            } else {
                                $cake_detail_array[$new_nexttonext_cake]['s_size'] = 0; 
                                $cake_detail_array[$new_nexttonext_cake]['l_size'] = 1; 
                                $cake_detail_array[$new_nexttonext_cake]['person_count'] = $cake_detail_array[$new_nexttonext_cake]['person_count'] + $cake_detail_array[$new_next_cake]['person_count'];

                                $new_nexttonext_cakekey = array_search($new_nexttonext_cake, $cake_date_array);
                                unset($cake_date_array[$new_nexttonext_cakekey]);
                            }
                            unset($cake_detail_array[$new_next_cake]);
                            $new_next_cakekey = array_search($new_next_cake, $cake_date_array);
                            unset($cake_date_array[$new_next_cakekey]);
                        }
                    }
                }
            }
        }
        //sort the final cake detail array
        sort($cake_detail_array);
        
        //array data to csv and store by create new or update cake_detail.csv file 
        $file = fopen('cake_detail.csv', 'w');
        foreach ($cake_detail_array as $line) {
            //put data into csv file
            fputcsv($file, $line);
        }
        fclose($file);
    }
}
