<?php
/**
 * PHP Integration of Open Flash Chart
 * Copyright (C) 2008 John Glazebrook <open-flash-chart@teethgrinder.co.uk>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

abstract class OFC_Dots_Base {

    function OFC_Dots_Base() {
        $this->type = $this->get_type();
    }

    function set_value($v) {
        $this->value = $v;
    }

    function set_position($v) {
        $this->position = $v;
    }

    function set_colour($v) {
        $this->colour = $v;
    }

    function set_tooltip($v) {
        $this->tooltip = $v;
    }

    function set_size($v) {
        $this->{"dot-size"} = $v;
    }

    function set_halo_size($v) {
        $this->{"halo-size"} = $v;
    }

    function set_alpha($v) {
        $this->alpha = $v;
    }

    function set_background_colour($v) {
        $this->{"background-colour"} = $v;
    }

    function set_background_alpha($v) {
        $this->{"background-alpha"} = $v;
    }

    function set_width($v) {
        $this->width = $v;
    }

    function set_tip($v) {
        $this->tip = $v;
    }

    function set_on_click($v) {
        $this->on_click = $v;
    }

    //return the type of dot
    abstract function get_type();

}

