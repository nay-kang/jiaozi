//
//  DumplingsTracker.m
//  DumplingsTracker
//
//  Created by lee on 2017/3/16.
//  Copyright © 2017年 chicv. All rights reserved.
//

#import "DumplingsTracker.h"

@interface DumplingsTracker ()

-(instancetype) init;

@end

@implementation DumplingsTracker

-(instancetype)init
{
    if (self = [super init]) {
        
    }
    return self;
}

+(instancetype)sharedTracker
{
    static DumplingsTracker *singleton = nil;
    static dispatch_once_t onceToken;
    dispatch_once(&onceToken, ^{
        singleton = [[DumplingsTracker alloc] init];
    });
    return singleton;
}

+(void)eventWithName:(NSString *)eventName parameters:(NSDictionary *)parameters
{
    
    NSData *jsonData = [NSJSONSerialization dataWithJSONObject:parameters
                                                       options:NSJSONWritingPrettyPrinted // Pass 0 if you don't care about the readability of the generated string
                                                         error:nil];
    DumplingsTracker *tracker = [self sharedTracker];
    NSString *jsonString = [[NSString alloc] initWithData:jsonData encoding:NSUTF8StringEncoding];
    if (!jsonString) {
        jsonString = @"";
    }
    NSURL *url = [NSURL URLWithString:[NSString stringWithFormat:@"https://jiaozi.stylewe.com/collect_img.gif?_jiaozi_uid=%@&pid=%@&type=event&category=%@&value=%@",tracker.idfa,tracker.pid,eventName,jsonString]];
    NSMutableURLRequest *request = [[NSMutableURLRequest alloc] initWithURL:url];
    request.timeoutInterval = 20;
    NSURLSession *session = [NSURLSession sessionWithConfiguration:[NSURLSessionConfiguration defaultSessionConfiguration] delegate:nil delegateQueue:[NSOperationQueue new]];
    
    //4.根据会话对象创建一个Task(发送请求）
    NSURLSessionDataTask *dataTask = [session dataTaskWithRequest:request];
    
    //5.执行任务
    [dataTask resume];
}

@end
