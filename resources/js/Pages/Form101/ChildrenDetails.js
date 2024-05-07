import React from "react";
import TextField from "./inputElements/TextField";
import DateField from "./inputElements/DateField";
import CheckBox from "./inputElements/CheckBox";
import { useTranslation } from "react-i18next";

export const childrenInitial = {
    firstName: "",
    IdNumber: "",
    Dob: "",
    inCustody: false,
    haveChildAllowance: false,
};
const ChildrenDetails = ({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) => {
    const { t } = useTranslation();

    return (
        <div>
            <h2>{t("form101.children_headline")}</h2>

            {values.children &&
                values.children.map((child, index) => (
                    <div key={index}>
                        <hr />
                        child {index + 1}{" "}
                        <button
                            type="button"
                            className="btn btn-sm btn-danger "
                            onClick={() => {
                                const newChildren = [...values.children];
                                newChildren.splice(index, 1);
                                setFieldValue("children", newChildren);
                            }}
                        >
                            -
                        </button>
                        <div className="row">
                            <div className="col-sm-4">
                                <TextField
                                    name={`children[${index}].firstName`}
                                    label="First Name"
                                    value={child.firstName}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].firstName &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].firstName
                                    }
                                    required
                                />
                            </div>
                            <div className="col-sm-4">
                                <TextField
                                    name={`children[${index}].IdNumber`}
                                    label="ID Number"
                                    value={child.IdNumber}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].IdNumber &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].IdNumber
                                    }
                                    required
                                />
                            </div>
                            <div className="col-sm-4">
                                <DateField
                                    name={`children[${index}].Dob`}
                                    label="Date of birth"
                                    value={child.Dob}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].Dob &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].Dob
                                    }
                                    required={true}
                                />
                            </div>
                        </div>
                        <div className="row">
                            <div className="col-sm-4">
                                <CheckBox
                                    name={`children[${index}].inCustody`}
                                    label="The child is in my custody"
                                    value={child.inCustody}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    checked={child.inCustody}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index].inCustody &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index].inCustody
                                    }
                                />
                            </div>
                            <div className="col-sm-4">
                                <CheckBox
                                    name={`children[${index}].haveChildAllowance`}
                                    label="I receive child allowance for the child from the National Insurance Institute"
                                    value={child.haveChildAllowance}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    checked={child.haveChildAllowance}
                                    error={
                                        touched.children &&
                                        touched.children[index] &&
                                        touched.children[index]
                                            .haveChildAllowance &&
                                        errors.children &&
                                        errors.children[index] &&
                                        errors.children[index]
                                            .haveChildAllowance
                                    }
                                />
                            </div>
                        </div>
                    </div>
                ))}
            <button
                type="button"
                className="btn btn-success button add slotBtn mb-3"
                onClick={() => {
                    setFieldValue("children", [
                        ...values.children,
                        childrenInitial,
                    ]);
                }}
            >
                + Add Child
            </button>
        </div>
    );
};

export default ChildrenDetails;
