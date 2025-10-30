import Vue from 'vue';
import moment from 'moment';
import BigNumber from 'bignumber.js';
import Numeral from '../lib/numeral';
import Utils from './Utils';

Vue.filter('timestampToDate', function (timestamp, pattern = 'YYYY-MM-DD HH:mm:ss') {
  return moment(timestamp, 'x').format(pattern);
});

Vue.filter('datetimeFormatter', function (timestamp, pattern = 'YYYY-MM-DD HH:mm:ss') {
  return moment(timestamp).format(pattern);
});

Vue.filter('diffCurrentTime', function (timestamp) {
  if (!timestamp) {
    return null;
  }
  const localTime = moment.utc(timestamp).local();
  const currentTime = moment();
  
  const formatters = [
    {milestoneTime: 60 , unit: 'minute', duration: 'minutes'},
    {milestoneTime: 24, unit: 'hour', duration: 'hours'},
    {milestoneTime: 7, unit: 'day', duration: 'days'},
    {milestoneTime: 30, unit: 'week', duration: 'weeks'},
    {milestoneTime: 12, unit: 'month', duration: 'months'},
    {milestoneTime: 10000000, unit: 'year', duration: 'years'},
  ];

  for (let item of formatters) {
    const diff = parseInt(currentTime.diff(localTime, item.duration));
    if (!diff) {
      return 'Online';
    }
    if (diff <= item.milestoneTime) {
      const unit = diff > 1 ? `${item.unit}s` : item.unit;
      return `${diff} ${unit} behind`;
    }
  }
  return null;
});

Vue.filter('timestampFormatter', function (timestamp) {
  if (!timestamp) {
    return null;
  }

  const datetime  = moment(timestamp);
  const today     = new Date();

  if (datetime.isSame(today, 'day')) {
    return datetime.format('hh:mm A');
  }

  if (datetime.isSame(today, 'week')) {
    return datetime.format('ddd');
  }

  if (!datetime.isSame(today, 'year')) {
    return datetime.format('YYYY-MM-DD');
  }

  return datetime.format('DD MMM');
});

Vue.filter('uppercase', (value) => {
  if (value) { return _.toUpper(`${value || ''}`); }
  return '';
});

Vue.filter( 'upperFirst', function (value) {
  return window._.upperFirst(value);
});

Vue.filter( 'uppercaseFirst', function (value) {
  return window._.startCase(value);
});

Vue.filter('formatCurrencyAmount', function(amount, zeroValue, lengthDecimal = 6) {
    const numberOfDecimalDigits = lengthDecimal;
    const format = numberOfDecimalDigits == 0 ?
      '0,0' :
      '0,0.[' + Array(numberOfDecimalDigits + 1).join('0') + ']';
    if (window._.isNil(zeroValue)) {
      zeroValue = '';
    }
    return (amount && parseFloat(amount) != 0) ? Numeral(amount).format(format) : zeroValue;
});

Vue.filter('formatRate', function(amount, zeroValue) {
    if (! parseFloat(amount)) {
      return 0;
    }
    const numberOfDecimalDigits = 1;
    const format = '0,0.[' + Array(numberOfDecimalDigits + 1).join('0') + ']';
    if (window._.isNil(zeroValue)) {
      zeroValue = '';
    }
    return (amount && parseFloat(amount) != 0) ? Numeral(amount).format(format) : zeroValue;
});

Vue.filter('toNumber', function (value) {
  const number = parseFloat(value);
  if (isNaN(number)) {
    return value;
  }
  // is e number (Ex: 1e-7)
  if (number.toString().includes('e')) {
    return Utils.trimEndZero(new BigNumber(`${value || 0}`).toFixed(20));
  }
  return number;
});

Vue.filter('formatDigitNumber', function (value, length = 2) {
  const number = parseFloat(value);
  if (isNaN(number)) {
    return value;
  }
  return new BigNumber(`${value || 0}`).toFixed(length, BigNumber.ROUND_DOWN);
});

Vue.filter('naturalPartOfNumber', function (number) {
  return Math.floor(number);
});

Vue.filter('fractionPartOfNumber', function (number) {
  const num = parseFloat(number);
  return Number(String(num).split('.')[1] || 0);
});

Vue.filter('sliceChar', function (value, length = 3) {
  return window._.chain(`${value}`)
                 .slice(0, length)
                 .join('')
                 .value();
});

Vue.filter('gameUsername', function (value) {
  return '@' + value;
});

Vue.filter('formatCoins', function (value, length = 0) {
  let number = parseFloat(value);
  if (isNaN(number)) {
    return value;
  }
  const units = [
    { milstoneValue: 1000, name: '', divValue: 1 },
    { milstoneValue: 1000000, name: 'K+', divValue: 1000 },
    { milstoneValue: 1000000000, name: 'M+', divValue: 1000000 },
    { milstoneValue: 1000000000000, name: 'B+', divValue: 1000000000 },
  ];
  number = new BigNumber(`${value || 0}`);
  for (let i=0; i < units.length; i++) {
    const unit = units[i];
    if (number.lt(unit.milstoneValue)) {
      return `${number.div(unit.divValue).toFixed(length, BigNumber.ROUND_DOWN)}${unit.name}`;
    }
  }
  return '1T+';
});

Vue.filter('timeWithTimezoneName', function (time) {
  let d = new Date();
  let timezone = d.getTimezoneOffset();
  let strTime =  time.from + ' ' + 'to' + ' ' + time.to + ' GMT' + (timezone <= 0 ? '+' : '-') + Math.abs(timezone / 60);
  return strTime;
});

Vue.filter('formatCountDownTime', function (value, char = '0', length = 2) {
  return window._.padStart(value, length, char);
})
