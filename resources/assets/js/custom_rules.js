import { Validator } from 'vee-validate';

Validator.extend('minDimensionsRule', {
  getMessage(field, [width, height], data) {
    return (data && data.message) || `The ${field} field size must be equal or greater than ${width}x${height} pixels.`;
  },
  validate(files, [width, height]) {
    const validateImage = (file, width, height) => {
      const URL = window.URL || window.webkitURL;
      return new Promise(resolve => {
        const image = new Image();
        image.onerror = () => resolve({ valid: false });
        image.onload = () => resolve({
          valid: image.width >= Number(width) && image.height >= Number(height) // only change from official rule
        });

        image.src = URL.createObjectURL(file);
      });
    };
    const list = [];
    for (let i = 0; i < files.length; i++) {
      if (! /\.(jpg|svg|jpeg|png|bmp|gif)$/i.test(files[i].name)) {
        return false;
      }
      list.push(files[i]);
    }
    return Promise.all(list.map(file => validateImage(file, width, height)));
  }
});

Validator.extend('maxDimensionsRule', {
  getMessage(field, [width, height], data) {
    return (data && data.message) || `The ${field} field size must be equal or less than ${width}x${height} pixels.`;
  },
  validate(files, [width, height]) {
    const validateImage = (file, width, height) => {
      const URL = window.URL || window.webkitURL;
      return new Promise(resolve => {
        const image = new Image();
        image.onerror = () => resolve({ valid: false });
        image.onload = () => resolve({
          valid: image.width <= Number(width) && image.height <= Number(height) // only change from official rule
        });

        image.src = URL.createObjectURL(file);
      });
    };
    const list = [];
    for (let i = 0; i < files.length; i++) {
      if (! /\.(jpg|svg|jpeg|png|bmp|gif)$/i.test(files[i].name)) {
        return false;
      }
      list.push(files[i]);
    }
    return Promise.all(list.map(file => validateImage(file, width, height)));
  }
});
