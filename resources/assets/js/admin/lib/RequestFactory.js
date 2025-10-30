import AdminRequest from '../requests/AdminRequest';
import UserRequest from '../requests/UserRequest';
import SiteSettingRequest from '../requests/SiteSettingRequest';
import MarketingMailRequest from '../requests/MarketingMailRequest';
import GameRequest from '../requests/GameRequest';
import SettingRequest from '../requests/SettingRequest';
import BountyRequest from '../requests/BountyRequest';
import SessionRequest from '../requests/SessionRequest';
import RoomRequest from '../requests/RoomRequest';
import CommunityRequest from '../requests/CommunityRequest';

const requestMap = {
  AdminRequest,
  UserRequest,
  SiteSettingRequest,
  MarketingMailRequest,
  GameRequest,
  SettingRequest,
  BountyRequest,
  SessionRequest,
  RoomRequest,
  CommunityRequest
};

const instances = {};

export default class RequestFactory {

  static getRequest(classname) {
    let RequestClass = requestMap[classname];
    if (!RequestClass) {
      throw new Error('Invalid request class name: ' + classname);
    }

    let requestInstance = instances[classname];
    if (!requestInstance) {
        requestInstance = new RequestClass();
        instances[classname] = requestInstance;
    }

    return requestInstance;
  }

}
