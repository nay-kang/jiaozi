<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Extensions\ElasticClient;

class GenerateExperimentsConfig extends Command{
    
    protected $signature = 'jiaozi:generate-experiments-config';
    
    protected $description = 'Generate Experiments Config for client';
    
    public function handle(){
        $profiles = config('profile');
        foreach($profiles as $profile_id=>$profile){
            $exp_distributes = [];
            foreach(array_get($profile,'experiments',[]) as $exp_id=>$experiment){
                $exp_stat = $this->getExpStat($profile_id, $exp_id, $experiment);
                $new_distrib = $this->rebalance($experiment, $exp_stat);
                $exp_distributes[] = [
                    'experiment_id' => $exp_id,
                    'start' => $experiment['start'],
                    'filter' => $experiment['filter'],
                    'traffic_in_exp' => $new_distrib['traffic_in_exp'],
                    'variations' => $new_distrib['variations']
                ];
                print_r($exp_stat);
            }
            $path = base_path('public/experiments/');
            if(!file_exists($path)){
                mkdir($path,0755,TRUE);
            }
            file_put_contents($path.'/'.$profile_id.'.json', json_encode($exp_distributes));
        }
    }
    
    protected function rebalance(array $experiment,array $stat){
        $traffic_in_exp = $experiment['traffic_in_exp'];
        $variations_count = $experiment['variations'];
        $variations_stat = [];
        $off_variation_traffic = $stat['-1'];
        $in_variation_traffic = 0;
        for($i=0;$i<$variations_count;$i++){
            $variations_stat[$i] = array_get($stat,$i,0);
            $in_variation_traffic += $variations_stat[$i];
        }
        if($in_variation_traffic+$off_variation_traffic==0){
            $new_in_variation_percent = $traffic_in_exp;
        }else{
            $new_in_variation_percent = round($traffic_in_exp*2-$in_variation_traffic/($in_variation_traffic+$off_variation_traffic),6);
        }
        
        $per_variation_percent = 1/$variations_count;
        $new_variation_percent = [];
        for($i=0;$i<$variations_count;$i++){
            if($in_variation_traffic==0){
                $_variation_percent = $per_variation_percent;
            }else{
                $_variation_percent = round($per_variation_percent*2-$variations_stat[$i]/$in_variation_traffic,6);
            }
            $new_variation_percent[] = [
                'weight' => $_variation_percent,
                'index' => $i,
            ];
        }
        return [
            'traffic_in_exp' => $new_in_variation_percent,
            'variations' => $new_variation_percent
        ];
    }
    
    protected function getExpStat($profile_id,$exp_id,$experiment){
        $query = [
            'size' => 0,
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'range' => [
                                'timestamp' => [
                                    'gte' => (new \DateTime($experiment['start']))->getTimestamp(),
                                    'format' => 'epoch_second'
                                ]
                            ]
                        ],
                        [
                            'term' => [
                                'profile_id' => $profile_id
                            ]
                        ],
                    ]
                ],
            ],
            'aggs' => [
                'uuids_count' => [
                    'cardinality' => [
                        'field' => 'uuid',
                    ]
                ]
            ]
        ];
        foreach($experiment['filter'] as $k=>$v){
            $query['query']['bool']['must'][] = [
                'term' => [
                    $k => $v
                ]
            ];
        }

        /*因为cardinality聚合和nested聚合有问题，所以只能采用循环的方式来获取分组数量*/
        $variations = [];
        for($variation=-1;$variation<($experiment['variations']);$variation++){
            $new_query = $query;
            $new_query['query']['bool']['must'][]['nested'] = [
                'path' => 'experiments',
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => [
                                    'experiments.id' => $exp_id
                                ]
                            ],
                            [
                                'term' => [
                                    'experiments.variation' => $variation
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            print_r(json_encode($new_query));
            $res = ElasticClient::getInstance()->search($new_query);
            $variations[$variation] = $res['aggregations']['uuids_count']['value'];
        }
        return $variations;
    }
}
?>