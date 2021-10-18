import * as React from 'react';
import {Route, Switch, Redirect} from "react-router-dom";
import * as ROUTES from './constants/routes';

import Layout from "./layouts/DefaultLayout";
import {Navigation} from "./components/Navigation";

const Home = React.lazy(() => import(/* webpackChunkName: "home", webpackPreload: true, webpackPrefetch: true */'./pages/Home'));
const Profile = React.lazy(() => import(/* webpackChunkName: "profile", webpackPreload: true, webpackPrefetch: true */'./pages/Profile'));

const App = () => {
  return <React.Fragment>
    <Layout>
      <Navigation />
      <Switch>
        <React.Suspense fallback={'<div>Loading...</div>'}>
          <Route render={() => <Redirect to={ROUTES.HOME} />} exact path={ROUTES.SLASH} />
          <Route render={() => <Home />} exact path={ROUTES.HOME} />
          <Route render={() => <Profile />} exact path={ROUTES.PROFILE} />
        </React.Suspense>
      </Switch>
    </Layout>
  </React.Fragment>
}

export default App;
