/* eslint-disable react/no-danger */
import React from "react";
import PropTypes from "prop-types";
import DOMPurify from "dompurify";

const HTMLRender = ({ children, className, style, component = "div", ...rest }) => {
  const html = DOMPurify.sanitize(children || "");
  const Component = component;

  return (
    <Component
      style={style}
      className={className}
      dangerouslySetInnerHTML={{ __html: html }}
      {...rest}
    />
  );
};

HTMLRender.propTypes = {
  children: PropTypes.string,
  className: PropTypes.string,
  style: PropTypes.shape({
    [PropTypes.string]: PropTypes.string
  }),
  component: PropTypes.elementType
};

export default HTMLRender;
