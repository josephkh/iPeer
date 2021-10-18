import * as React from 'react';
import {Link, RouteProps} from 'react-router-dom'
import * as ROUTES from './../constants/routes';

const LOGO = require('./../assets/img/ipeer_logo.png');

const DefaultLayout = (props: RouteProps) => {

  return <div className={'containerOuter pagewidth'}>
    <div id="bannerLarge" className="banner">
      <div id="ipeerLogo">
        <Link to={ROUTES.HOME} id="home">
          <img src={LOGO} id="bannerLogoImgLeft" alt="logo" />
          <span id="ipeerI">i</span>
          <span id="ipeerText">Peer</span>
          <span id="bannerLogoText">3.4.8 with TeamMaker</span>
        </Link>
      </div>
      <div id="customLogo" />
    </div>
    {props.children}
  </div>
}
export default DefaultLayout;
