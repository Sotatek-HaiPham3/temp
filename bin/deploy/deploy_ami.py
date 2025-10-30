import boto3
import socket
import os
import sys
import commands
import time
import pexpect

ec2 = boto3.resource('ec2', region_name='us-west-1')
oldLaunchConfigration=''

def getInstanceId(instance):
    return instance['InstanceId']

def deployToRunningInstance(instance):
    print '========== deployToRunningInstance ' + instance.id + ' START =========='

    privateIp = instance.private_ip_address

    cmd = "./deploy.sh root@%s 1" % (privateIp)
    os.system(cmd)

    print '========== copyEnv =========='
    scpCommand = "scp -P 22 /root/gamelancer/.env root@%s:/var/www/gamelancer" % (privateIp)
    os.system(scpCommand)

    print '========== deployToRunningInstance ' + instance.id + ' END =========='

def createIntanceImage():
    print '========== createIntanceImage START =========='

    global oldLaunchConfigration

    clientScaling = boto3.client('autoscaling', region_name='us-west-1')
    response = clientScaling.describe_launch_configurations(
        LaunchConfigurationNames=[
            oldLaunchConfigration,
        ],
    )

    imageId = response['LaunchConfigurations'][0]['ImageId']

    ec2 = boto3.resource('ec2')

    print '========== create tempory instance =========='
    instances = ec2.create_instances(
        ImageId=imageId,
        InstanceType='c5.large',
        MinCount=1,
        MaxCount=1,
        SecurityGroups=[
            'default'
        ]
    )
    instance = instances[0];
    instance.wait_until_running(Filters=[{'Name':'instance-state-name','Values':['running']}])

    retries = 20
    retry_delay=10
    retry_count = 0
    while retry_count <= retries:
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        result = sock.connect_ex((instance.private_ip_address, 22))
        if result == 0:
            print "Instance is UP & accessible on port 22, the IP address is:  " + instance.private_ip_address
            deployToRunningInstance(instance)
            break
        else:
            print "instance is still down retrying . . . "
            time.sleep(retry_delay)
        retry_count = retry_count + 1

    backendTag = commands.getoutput('cd /root/gamelancer && git describe --abbrev=0 --tags')
    name = 'gamelancer-web-' + backendTag + "-" + str(int(time.time()))

    client = boto3.client('ec2')

    print "Instance ID " + instance.id
    response = client.create_image(
        Description=name,
        InstanceId=instance.id,
        Name=name,
    )
    image = ec2.Image(response['ImageId'])

    image.wait_until_exists(Filters=[{'Name': 'state', 'Values': ['available']}])

    print '========== delete tempory instance =========='

    client.terminate_instances(
       InstanceIds=[
           instance.id,
       ],
    )

    print '========== createdIntanceImage ' + response['ImageId'] + ' =========='

    createLaunchConfigration(image)


def createLaunchConfigration(image):
    print '========== createLaunchConfigration START =========='

    client = boto3.client('autoscaling', region_name='us-west-1')
    backendTag = commands.getoutput('cd /root/gamelancer && git describe --abbrev=0 --tags')

    name = 'gamelancer-web-launch-configration-' + backendTag + "-" + str(int(time.time()))

    response = client.create_launch_configuration(
        LaunchConfigurationName=name,
        ImageId=image.id,
        InstanceType='t2.medium',
        SecurityGroups=[
            'default',
            'gamelancer-web',
        ]
    )

    if response['ResponseMetadata']['HTTPStatusCode'] == 200 :
        print '========== createdLaunchConfigration ' + name + ' =========='

        updateScalingGroupWithNewLaunchConfigration(client, name)
    else :
        print '========== createdLaunchConfigration FAIL =========='

def updateScalingGroupWithNewLaunchConfigration (client, name):
    print '========== updatedScalingGroupWithNewLaunchConfigration START =========='

    response = client.update_auto_scaling_group(
        AutoScalingGroupName='gamelancer-web-auto-scaling-group',
        LaunchConfigurationName=name
    )

    if response['ResponseMetadata']['HTTPStatusCode'] == 200 :
        print '========== updatedScalingGroupWithNewLaunchConfigration End =========='

        deleteLaunchConfigration(client)
    else :
        print '========== updatedScalingGroupWithNewLaunchConfigration FAIL =========='

def deleteLaunchConfigration(client) :
    global oldLaunchConfigration

    print '========== deletedLaunchConfigration ' + oldLaunchConfigration + ' START =========='

    response = client.describe_launch_configurations(
        LaunchConfigurationNames=[
            oldLaunchConfigration,
        ],
    )

    configuration = response['LaunchConfigurations'][0]

    imageId = configuration['ImageId']
    client.delete_launch_configuration(
        LaunchConfigurationName=oldLaunchConfigration,
    )
    clientEc2 = boto3.client('ec2')
    clientEc2.deregister_image(
        ImageId=imageId,
    )

    print '========== deletedLaunchConfigration ' + oldLaunchConfigration + ' END =========='

def getInstanceToDeploy():
    print '========== getInstanceToDeploy =========='
    client = boto3.client('autoscaling', region_name='us-west-1')
    response  = client.describe_auto_scaling_groups(
        AutoScalingGroupNames=[
        'gamelancer-web-auto-scaling-group',
    ])
    group = response['AutoScalingGroups'][0]

    global oldLaunchConfigration

    oldLaunchConfigration = group['LaunchConfigurationName']
    instanceIds = [getInstanceId(i) for i in group['Instances']]
    runningInstances = ec2.instances.filter(InstanceIds=instanceIds)

    createIntanceImage()
    for instance in runningInstances:
        deployToRunningInstance(instance)


if __name__ == '__main__':
    print '========== START =========='
    getInstanceToDeploy()
    deployToQueueAndOrder = './deploy_without_confirm.sh'
    os.system(deployToQueueAndOrder)
    sendEmail = './send_deploy_mail.sh duong.ngo@sotatek.com'
    os.system(sendEmail)
    print '========== END =========='
