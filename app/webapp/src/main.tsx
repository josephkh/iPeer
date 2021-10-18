import * as React from 'react';
import { render } from 'react-dom'
import {HashRouter as Router} from 'react-router-dom';
import { Provider } from 'react-redux';
import App from './App';
import './assets/sass/index.scss'
import { store } from './store';

render(
  <Router hashType={'noslash'}>
    <Provider store={store}>
      <App/>
    </Provider>
  </Router>
  , document.getElementById('root')
);
