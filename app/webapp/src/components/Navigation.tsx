import * as React from 'react';
import {NavLink} from 'react-router-dom'
import * as ROUTES from './../constants/routes';
import {useSelector} from "react-redux";
const SPINNER = require('./../assets/img/spinner.gif');

export const Navigation = () => {

  // @ts-ignore
  const {loading, profile} = useSelector((state) => state.user)

  const Spinner = () => <div><img src={SPINNER} alt={'spinner'} /> loading...</div>;

  return <div id="navigationOuter" className="navigation">
    <ul>
      <li><NavLink to={ROUTES.HOME}>Home</NavLink></li>
      <li><a href="http://localhost:8080/logout" className="miniLinks">Logout</a></li>
      {loading ? <Spinner /> : <li><NavLink to={ROUTES.PROFILE} className="miniLinks">{profile.first_name} {profile.last_name}</NavLink></li>}
    </ul>
  </div>
}
